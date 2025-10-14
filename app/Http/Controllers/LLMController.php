<?php

namespace App\Http\Controllers;

use App\Models\Conversation; // Pastikan ini ada di atas
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;
use Smalot\PdfParser\Parser;
use Illuminate\Support\Str;

class LLMController extends Controller
{
    // Load the Blade view
    public function view()
{
    return view('chat');
}

    // Handle form submission
    public function ask(Request $request)
    {
        $text = $request->input('text');
        $mode = 'text'; // Default mode

        // Check if a PDF was uploaded
        if ($request->hasFile('pdf')) {
            $mode = 'pdf'; // Set mode to pdf
            try {
                $parser = new Parser();
                $pdf = $parser->parseFile($request->file('pdf')->getRealPath());
                $text = $pdf->getText();
            } catch (\Exception $e) {
                return back()->with('response', 'Error reading PDF file: ' . $e->getMessage());
            }
        }

        if (!$text) {
            return back()->with('response', 'Please enter text or upload a PDF.');
        }

        $response = Http::timeout(120)->post(env('LLM_API_URL'), [
            'model' => 'stabilityai/stablelm-2-zephyr-1_6b',
            'messages' => [
                ['role' => 'system', 'content' => 'Anda adalah seorang ahli yang pandai membuat ringkasan teks.'],
                ['role' => 'user', 'content' => "Tolong buatkan ringkasan dari teks berikut. Berikan ringkasan dengan bahasa yang baik dan jelas dan memuat poin dari teks:\n\n" . Str::limit($text, 4000, '')],
            ],
            'max_tokens' => 1024,
            'temperature' => 0.5,
        ]);
        
        if ($response->failed()) {
            return back()->with('response', 'Failed to connect to the LLM Studio API. Is the server running?');
        }

        $summary = $response->json('choices.0.message.content') ?? 'No valid response from model.';

        // --- PENEMPATAN YANG BENAR ADA DI SINI ---
        // Simpan input dan respons ke database
        Conversation::create([
            'mode' => $mode,
            'input' => $text,
            'response' => $summary,
        ]);
        // -----------------------------------------

        return redirect('/chat')->with('response', $summary)->withInput();
    }
    // Method to display recent projects
    public function history()
{
    // Ambil semua percakapan, urutkan dari yang paling baru
    $conversations = Conversation::latest()->get(); 

    // Tampilkan view baru bernama 'recent-projects' dan kirim data percakapannya
    return view('recent-projects', compact('conversations'));
}
    public function show(Conversation $conversation)
    {
        // Laravel akan otomatis menemukan data Conversation berdasarkan ID dari URL
        return view('project-detail', compact('conversation'));
    }
    // --- METHOD BARU UNTUK FITUR Q&A ---

    /**
     * Menampilkan halaman utama Q&A dengan daftar dokumen.
     */
    public function qnaIndex()
    {
        $documents = \App\Models\Document::latest()->get();
        return view('qna.index', compact('documents'));
    }

    /**
     * Memproses upload dokumen, menyimpannya, dan redirect ke halaman chat.
     */
    public function qnaUpload(Request $request)
    {
        $request->validate(['document' => 'required|file|mimes:pdf,txt,docx|max:10240']); // max 10MB

        $file = $request->file('document');
        $originalName = $file->getClientOriginalName();
        $textContent = '';

        try {
            $parser = new \Smalot\PdfParser\Parser();
            $pdf = $parser->parseFile($file->getRealPath());
            $textContent = $pdf->getText();
        } catch (\Exception $e) {
            return back()->with('error', 'Failed to parse PDF file. Error: ' . $e->getMessage());
        }

        if (empty($textContent)) {
            return back()->with('error', 'Could not extract text from the document.');
        }

        // Simpan dokumen ke database
        $document = \App\Models\Document::create([
            'original_filename' => $originalName,
            'content' => $textContent,
        ]);

        return redirect()->route('qna.chat', $document);
    }

    /**
     * Menampilkan halaman chat untuk dokumen tertentu.
     */
    public function qnaChat(\App\Models\Document $document)
    {
        return view('qna.chat', compact('document'));
    }

    /**
     * Menerima pertanyaan dan memberikan jawaban dari AI.
     */
    public function qnaAsk(Request $request, \App\Models\Document $document)
    {
        $request->validate(['question' => 'required|string']);

        $question = $request->question;
        $context = $document->content;

        // Memotong konteks agar tidak terlalu panjang untuk API call
        $context = Str::limit($context, 4000, '');

        $response = Http::timeout(120)->post(env('LLM_API_URL'), [
            'model' => 'stabilityai/stablelm-2-zephyr-1_6b',
            'messages' => [
                ['role' => 'system', 'content' => 'You are a helpful assistant that answers questions based on the provided text.'],
                ['role' => 'user', 'content' => "Based on the following text, answer the question.\n\nText:\n\"{$context}\"\n\nQuestion: {$question}\n\nAnswer in Indonesian:"],
            ],
            'max_tokens' => 500,
            'temperature' => 0.3,
        ]);

        $answer = $response->json('choices.0.message.content') ?? 'Sorry, I could not find an answer.';

        // Kita akan kembalikan sebagai JSON untuk chat interaktif nanti
        return response()->json(['answer' => $answer]);
    }
    public function destroyProject(\App\Models\Conversation $conversation)
{
    // Hapus record dari database
    $conversation->delete();

    // Redirect kembali ke halaman riwayat dengan pesan sukses
    return redirect()->route('projects.history')->with('success', 'Project has been deleted successfully.');
}
}

