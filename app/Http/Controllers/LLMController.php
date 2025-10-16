<?php

namespace App\Http\Controllers;

use App\Models\Conversation; // Pastikan ini ada di atas
use App\Models\Document;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;
use Smalot\PdfParser\Parser;
use Illuminate\Support\Str;
use App\Models\FlashcardSet;

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
    $textContent = $request->input('text');
    $originalName = 'Pasted Text'; // Default filename untuk teks yang ditempel

    // 1. Ekstrak teks jika ada file PDF yang diunggah
    if ($request->hasFile('pdf')) {
        $file = $request->file('pdf');
        $originalName = $file->getClientOriginalName();
        try {
            $parser = new \Smalot\PdfParser\Parser();
            $pdf = $parser->parseFile($file->getRealPath());
            $textContent = $pdf->getText();
        } catch (\Exception $e) {
            return back()->with('response', 'Error reading PDF file: ' . $e->getMessage());
        }
    }

    // Validasi jika tidak ada teks sama sekali
    if (!$textContent) {
        return back()->with('response', 'Please enter text or upload a PDF.');
    }

    $textContent = preg_replace('/\s+/', ' ', trim($textContent));

    // 2. (PERUBAHAN UTAMA) Simpan konten asli ke tabel 'documents'
    $document = Document::create([
        'original_filename' => $originalName,
        'content' => $textContent,
    ]);

        $response = Http::timeout(120)->post(env('LLM_API_URL'), [
            'model' => 'stabilityai/stablelm-2-zephyr-1_6b',
            'messages' => [
                ['role' => 'system', 'content' => 'Anda adalah seorang ahli yang pandai membuat ringkasan teks.'],
                ['role' => 'user', 'content' => "Tolong buatkan ringkasan dari teks berikut. Berikan ringkasan dengan bahasa yang baik dan jelas dan memuat poin dari teks:\n\n" . Str::limit($textContent, 4000, '')],
            ],
            'max_tokens' => 1024,
            'temperature' => 0.5,
        ]);
        
        if ($response->failed()) {
            return back()->with('response', 'Failed to connect to the LLM Studio API. Is the server running?');
        }

        $summary = $response->json('choices.0.message.content') ?? 'No valid response from model.';

        // 4. (PERUBAHAN UTAMA) Simpan ringkasan ke tabel 'conversations' DAN hubungkan ke dokumen
    Conversation::create([
        'document_id' => $document->id, // Menghubungkan ringkasan ini ke dokumen aslinya
        'mode' => 'summarize',
        'input' => $textContent, // Input sekarang adalah isi lengkap dokumen
        'response' => $summary,
    ]);

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
        // Ambil semua percakapan, urutkan dari yang paling baru
        $conversation->load('document'); // Eager load relasi document

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

    public function generateFlashcards(Document $document)
{
    // 1. Siapkan konteks dan prompt khusus untuk AI
    $context = Str::limit($document->content, 2000, ''); // Batasi konteks
    $prompt = "Based on the following text, create a set of questions and answers suitable for flashcards. Provide the output as a valid JSON array of objects. Each object must have two keys: 'term' for the question, and 'definition' for the answer. Create between 3 to 6 flashcards.\n\nExample format: [{\"term\": \"What is the capital of Indonesia?\", \"definition\": \"Jakarta.\"}]\n\nText:\n\"{$context}\"";

    // 2. Kirim permintaan ke AI
    $response = Http::timeout(180)->post(env('LLM_API_URL'), [
        'model' => 'stabilityai/stablelm-2-zephyr-1_6b', // Pastikan ini model yang Anda gunakan
        'messages' => [
            ['role' => 'system', 'content' => 'You are a helpful assistant that creates flashcards in JSON format.'],
            ['role' => 'user', 'content' => $prompt],
        ],
        'temperature' => 0.5,
        'max_tokens' => 1500,
    ]);

    if ($response->failed()) {
        return back()->with('error', 'Failed to connect to the AI model.');
    }

    $aiResponseContent = $response->json('choices.0.message.content');

    // 3. Proses respons JSON dari AI
    try {
        $flashcardsData = json_decode($aiResponseContent, true, 512, JSON_THROW_ON_ERROR);
    } catch (\JsonException $e) {
        // Jika AI tidak mengembalikan JSON yang valid
        return back()->with('error', 'The AI did not return a valid format. Please try again.');
    }

    if (!is_array($flashcardsData) || empty($flashcardsData)) {
        return back()->with('error', 'The AI could not generate flashcards from this document.');
    }

    // 4. Simpan ke database
    // Buat set flashcard baru
    $flashcardSet = FlashcardSet::create([
        'title' => 'AI Flashcards for: ' . Str::limit($document->original_filename, 50),
        'description' => 'Automatically generated from a document.',
    ]);

    // Simpan setiap kartu
    foreach ($flashcardsData as $cardData) {
        // Pastikan formatnya benar sebelum menyimpan
        if (isset($cardData['term']) && isset($cardData['definition'])) {
            $flashcardSet->flashcards()->create([
                'term' => $cardData['term'],
                'definition' => $cardData['definition'],
            ]);
        }
    }

    // 5. Arahkan pengguna ke halaman untuk melihat set flashcard yang baru dibuat
    return redirect()->route('flashcards.show', $flashcardSet)->with('success', 'AI has successfully generated your flashcards!');
}
}

