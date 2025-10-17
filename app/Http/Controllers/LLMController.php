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

        $response = Http::withToken(env('MISTRAL_API_KEY'))->post(env('LLM_API_URL'), [
            'model' => 'mistral-small-latest',
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

        $summaryMarkdown = $response->json('choices.0.message.content') ?? 'No valid response from model.';

        // Proses Markdown menjadi HTML
        $parsedown = new \Parsedown();
        $summaryHtml = $parsedown->text($summaryMarkdown);

        // 4. (PERUBAHAN UTAMA) Simpan ringkasan ke tabel 'conversations' DAN hubungkan ke dokumen
    Conversation::create([
        'document_id' => $document->id, // Menghubungkan ringkasan ini ke dokumen aslinya
        'mode' => 'summarize',
        'input' => $textContent, // Input sekarang adalah isi lengkap dokumen
        'response' => $summaryMarkdown,
    ]);

    return redirect('/chat')->with('response', $summaryHtml)->withInput();
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

        $response = Http::withToken(env('MISTRAL_API_KEY'))->post(env('LLM_API_URL'), [
            'model' => 'mistral-small-latest',
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

 /*  public function generateFlashcards(Document $document)
{
    set_time_limit(300); // Perpanjang batas waktu eksekusi jika perlu

    $context = Str::limit($document->content, 2500, ''); 
    $prompt = "Berdasarkan teks berikut, buatlah satu set pertanyaan dan jawaban untuk flashcard. Berikan output dalam format JSON array yang valid. Setiap objek harus memiliki kunci 'term' untuk pertanyaan dan 'definition' untuk jawaban. Buat antara 1 sampai 3 flashcard.\n\nContoh format: [{\"term\": \"Siapa nama tokoh utama?\", \"definition\": \"Arga.\"}]\n\nTeks:\n\"{$context}\"";

    // KUNCI PERBAIKAN: Pindahkan ->timeout(180) SEBELUM ->post()
    $response = Http::withToken(env('MISTRAL_API_KEY'))
        ->timeout(180) 
        ->post(env('LLM_API_URL'), [
            'model' => 'mistral-small-latest',
            'messages' => [
                ['role' => 'system', 'content' => 'Anda adalah asisten yang membantu membuat flashcard dalam format JSON.'],
                ['role' => 'user', 'content' => $prompt],
            ],
            'temperature' => 0.5,
            'max_tokens' => 1500,
        ]);

    if ($response->failed()) {
        return back()->with('error', 'The AI service timed out or failed. Please try again. Error: ' . $response->body());
    }

    $aiResponseContent = $response->json('choices.0.message.content');

    try {
        $flashcardsData = json_decode($aiResponseContent, true, 512, JSON_THROW_ON_ERROR);
    } catch (\JsonException $e) {
        return back()->with('error', 'The AI did not return a valid format. Please try again.');
    }

    if (!is_array($flashcardsData) || empty($flashcardsData)) {
        return back()->with('error', 'The AI could not generate flashcards from this document.');
    }

    $flashcardSet = FlashcardSet::create([
        'title' => 'AI Flashcards for: ' . Str::limit($document->original_filename, 50),
        'description' => 'Automatically generated from a document.',
    ]);

    foreach ($flashcardsData as $cardData) {
        if (isset($cardData['term']) && isset($cardData['definition'])) {
            $flashcardSet->flashcards()->create([
                'term' => $cardData['term'],
                'definition' => $cardData['definition'],
            ]);
        }
    }

    return redirect()->route('flashcards.show', $flashcardSet)->with('success', 'AI has successfully generated your flashcards!');
}
*/
public function saveAiFlashcards(Request $request)
{
    $request->validate([
        'title' => 'required|string',
        'description' => 'nullable|string',
        'flashcardsData' => 'required|array',
    ]);

    // 1. Buat set flashcard baru
    $flashcardSet = FlashcardSet::create([
        'title' => $request->title,
        'description' => $request->description,
    ]);

    // 2. Simpan setiap kartu
    foreach ($request->flashcardsData as $cardData) {
        if (isset($cardData['term']) && isset($cardData['definition'])) {
            $flashcardSet->flashcards()->create([
                'term' => $cardData['term'],
                'definition' => $cardData['definition'],
            ]);
        }
    }

    // 3. Kembalikan URL untuk redirect
    return response()->json([
        'redirect_url' => route('flashcards.show', $flashcardSet)
    ]);
}}