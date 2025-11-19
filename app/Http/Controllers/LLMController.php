<?php

namespace App\Http\Controllers;

use Illuminate\Support\Facades\Auth;
use App\Models\Conversation;
use App\Models\Document;
use App\Models\FlashcardSet;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Str;

class LLMController extends Controller
{
    // Method untuk menampilkan halaman utama Summarizer
    public function view()
    {
        return view('chat');
    }

    // Method untuk memproses ringkasan
    public function ask(Request $request)
    {
        $textContent = $request->input('text');
        $originalName = 'Pasted Text';

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

        if (!$textContent) {
            return back()->with('response', 'Please enter text or upload a PDF.');
        }

        $textContent = preg_replace('/\s+/', ' ', trim($textContent));

        // Dokumen tidak perlu dibuat di sini, ia akan dibuat di bawah HANYA jika Auth::check()
        // Ini menghindari pembuatan duplikat
        // $document = Document::create([ ... ]);

        $prompt = "Anda adalah seorang ahli yang pandai membuat ringkasan teks. Tolong buatkan ringkasan dari teks berikut. Berikan ringkasan dengan bahasa yang baik dan jelas dan memuat poin dari teks:\n\n" . Str::limit($textContent, 30000, '');

        // --- START OLLAMA MIGRATION ---
        $apiResponse = $this->callOllamaAPI($prompt);

        if (!$apiResponse->success) {
            return back()->with('response', 'Failed to connect to the OLLAMA API. Error: ' . $apiResponse->body);
        }

        $summaryMarkdown = $apiResponse->text ?? 'No valid response from model.';
        // --- END OLLAMA MIGRATION ---


        $parsedown = new \Parsedown();
        $summaryHtml = $parsedown->text($summaryMarkdown);

        if (Auth::check()) {
            // 1. Simpan dokumen
            $document = Document::create([
                'original_filename' => $originalName,
                'content' => $textContent,
                // 'user_id' => Auth::id() // Opsional, jika Anda ingin melangkah lebih jauh
            ]);

            // 2. Simpan ringkasan
            Conversation::create([
                'document_id' => $document->id,
                'mode' => 'summarize',
                'input' => $textContent,
                'response' => $summaryMarkdown,
                // 'user_id' => Auth::id() // Opsional
            ]);
        }
        return redirect('/chat')->with('response', $summaryHtml)->withInput();
    }

    // --- FITUR RIWAYAT (RECENT PROJECTS) ---
    public function history()
    {
        $conversations = Conversation::latest()->get();
        return view('recent-projects', compact('conversations'));
    }

    public function show(Conversation $conversation)
    {
        $conversation->load('document');
        return view('project-detail', compact('conversation'));
    }

    public function destroyProject(Conversation $conversation)
    {
        $conversation->delete();
        return redirect()->route('projects.history')->with('success', 'Project has been deleted successfully.');
    }

    // --- FITUR Q&A DENGAN DOKUMEN ---
    public function qnaIndex()
    {
        $documents = Document::latest()->get();
        return view('qna.index', compact('documents'));
    }

    public function qnaUpload(Request $request)
    {
        $request->validate(['document' => 'required|file|mimes:pdf,txt,docx|max:10240']);
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

        $document = Document::create([
            'original_filename' => $originalName,
            'content' => $textContent,
        ]);

        return redirect()->route('qna.chat', $document);
    }

    public function qnaChat(Document $document)
    {
        return view('qna.chat', compact('document'));
    }

    public function qnaAsk(Request $request, Document $document)
    {
        $request->validate(['question' => 'required|string']);
        $question = $request->question;
        $context = Str::limit($document->content, 30000, '');

        $prompt = "Based on the following text, answer the question.\n\nText:\n\"{$context}\"\n\nQuestion: {$question}\n\nAnswer in Indonesian:";

        // --- START OLLAMA MIGRATION ---
        $apiResponse = $this->callOllamaAPI($prompt);

        if (!$apiResponse->success) {
            return response()->json(['answer' => 'Sorry, I failed to connect to the OLLAMA API. Error: ' . $apiResponse->body]);
        }

        $answer = $apiResponse->text ?? 'Sorry, I could not find an answer.';
        // --- END OLLAMA MIGRATION ---

        return response()->json(['answer' => $answer]);
    }

    public function destroyDocument(Document $document)
    {
        $document->delete();
        return redirect()->route('qna.index')->with('success', 'Document has been deleted successfully.');
    }

    // --- FITUR GENERATE FLASHCARD (SERVER-SIDE) ---
    public function generateFlashcards(Document $document)
    {
        set_time_limit(300);

        try {
            $context = Str::limit($document->content, 2500, '');
            // Prompt tidak berubah
            $prompt = "Berdasarkan teks berikut, buatlah satu set pertanyaan dan jawaban untuk flashcard. PENTING: Respons Anda HARUS HANYA berupa JSON array yang valid. Setiap objek harus memiliki kunci 'term' untuk pertanyaan dan 'definition' untuk jawaban. Jangan sertakan teks atau penjelasan lain sebelum atau sesudah JSON array. Buat antara 2 sampai 4 flashcard.\n\nContoh format: [{\"term\": \"Siapa nama tokoh utama?\", \"definition\": \"Arga.\"}]\n\nTeks:\n\"{$context}\"";

            // --- START OLLAMA MIGRATION ---
            $apiResponse = $this->callOllamaAPI($prompt);

            if (!$apiResponse->success) {
                throw new \Exception('The AI service failed to respond. Error: ' . $apiResponse->body);
            }

            $aiResponseContent = $apiResponse->text;
            // --- END OLLAMA MIGRATION ---


            // Logika pembersihan JSON ini SANGAT PENTING untuk OLLAMA,
            // karena model mungkin masih membungkusnya dengan ```json
            $cleanedJsonString = trim(str_replace(['```json', '```'], '', $aiResponseContent));
            $flashcardsData = json_decode($cleanedJsonString, true);

            if (json_last_error() !== JSON_ERROR_NONE) {
                // Jika format JSON gagal, berikan pesan error yang lebih jelas
                throw new \Exception('The AI returned an invalid format. Response: ' . $cleanedJsonString);
            }

            $flashcardSet = FlashcardSet::create([
                'title' => 'AI Flashcards for: ' . Str::limit($document->original_filename, 50),
                'description' => 'Automatically generated from a document.',
            ]);

            foreach ($flashcardsData as $cardData) {
                if (isset($cardData['term']) && isset($cardData['definition'])) {
                    $flashcardSet->flashcards()->create($cardData);
                }
            }

            return redirect()->route('flashcards.show', $flashcardSet)->with('success', 'AI has successfully generated your flashcards!');
        } catch (\Exception $e) {
            return back()->with('error', $e->getMessage());
        }
    }


    /**
     * =================================================================
     * HELPER METHOD BARU UNTUK OLLAMA API
     * =================================================================
     *
     * Ini menggantikan panggilan Http::post() yang berulang-ulang ke Gemini.
     */
    private function callOllamaAPI(string $prompt)
    {
        // 1. Ambil konfigurasi dari file .env
        $apiUrlBase = env('OLLAMA_API_URL');
        if (!$apiUrlBase) {
            return (object)[
                'success' => false,
                'body' => 'OLLAMA_API_URL is not set in your .env file.'
            ];
        }

        $apiUrl = rtrim($apiUrlBase, '/') . '/generate'; // Sesuai dokumentasi Anda
        $model = env('OLLAMA_MODEL', 'qwen2.5:14b');
        $temperature = (float) env('OLLAMA_TEMPERATURE', 0.1);

        // 2. Susun payload sesuai dokumentasi OLLAMA Anda
        $payload = [
            'model' => $model,
            'prompt' => $prompt,
            'temperature' => $temperature,
            'stream' => false,
        ];

        // 3. (PENTING) Tambahkan 'format: json' jika prompt meminta JSON
        // Ini adalah fitur umum OLLAMA untuk memastikan output JSON yang bersih.
        // Ini SANGAT membantu untuk fitur 'generateFlashcards'.
        if (str_contains(strtoupper($prompt), 'JSON')) {
            $payload['format'] = 'json';
        }

        // 4. Lakukan panggilan API
        $response = Http::timeout(180)->post($apiUrl, $payload);

        // 5. Tangani kegagalan
        if ($response->failed()) {
            return (object)['success' => false, 'body' => $response->body()];
        }

        // 6. Parsing respons
        // Dokumentasi Anda sedikit ambigu (tertulis "string"),
        // tetapi API OLLAMA /generate standar mengembalikan JSON dengan kunci 'response'.
        $responseText = $response->json('response');

        if ($responseText === null) {
            // Fallback jika API Anda *benar-benar* hanya mengembalikan string mentah
            $body = $response->body();
            if (is_string($body)) {
                $responseText = $body;
            } else {
                return (object)[
                    'success' => false,
                    'body' => 'Invalid or empty response from OLLAMA. Full response: ' . $response->body()
                ];
            }
        }

        return (object)['success' => true, 'text' => $responseText];
    }
}