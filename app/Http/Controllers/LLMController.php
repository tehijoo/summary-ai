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
    const MAX_FILE_SIZE = 10 * 1024 * 1024; // 10MB
    const SUPPORTED_FORMATS = ['pdf'];

    // Method untuk menampilkan halaman utama Summarizer
    public function view()
    {
        return view('chat', [
            'maxFileSize' => self::MAX_FILE_SIZE / (1024 * 1024),
        ]);
    }

    // Method untuk memproses ringkasan
    public function ask(Request $request)
    {
        \Log::info('Ask method called', [
            'has_file' => $request->hasFile('pdf'),
            'method' => $request->method(),
        ]);

        $textContent = $request->input('text');
        $originalName = 'Pasted Text';
        $filePath = null; // NEW: Store file path

        if ($request->hasFile('pdf')) {
            $file = $request->file('pdf');
            
            // Validate file before processing
            $validation = $this->validateUploadedFile($file);
            if ($validation !== true) {
                \Log::info('File validation failed', ['error' => $validation]);
                return redirect()->back()->with('error', $validation)->withInput();
            }

            $originalName = $file->getClientOriginalName();
            
            try {
                $parser = new \Smalot\PdfParser\Parser();
                $pdf = $parser->parseFile($file->getRealPath());
                $textContent = $pdf->getText();
                
                if (empty(trim($textContent))) {
                    return redirect()->back()
                        ->with('error', 'Could not extract text from PDF. File may be empty or corrupted.')
                        ->withInput();
                }

                // NEW: Save the PDF file just like in qnaUpload
                $filePath = $file->store('documents', 'public');
                
            } catch (\Exception $e) {
                \Log::error('PDF parsing error', ['error' => $e->getMessage()]);
                return redirect()->back()
                    ->with('error', 'Error reading PDF file: ' . $e->getMessage())
                    ->withInput();
            }
        }

        if (!$textContent) {
            return redirect()->back()
                ->with('error', 'Please provide text or upload a PDF file.')
                ->withInput();
        }

        // Truncate extremely long content
        if (strlen($textContent) > 50000) {
            $textContent = substr($textContent, 0, 50000) . "\n\n[Content truncated due to length]";
        }

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
            // 1. Simpan dokumen WITH file_path
            $document = Document::create([
                'user_id' => Auth::id(),
                'original_filename' => $originalName,
                'file_path' => $filePath, // NEW: Save the PDF path
                'content' => $textContent,
            ]);

            // 2. Simpan ringkasan
            Conversation::create([
                'user_id' => Auth::id(),
                'document_id' => $document->id,
                'mode' => 'summarize',
                'input' => $textContent,
                'response' => $summaryMarkdown,
            ]);
        }

        return redirect('/chat')->with('response', $summaryHtml)->withInput();
    }

    // --- FITUR RIWAYAT (RECENT PROJECTS) ---
    public function history()
    {
        $conversations = Conversation::where('user_id', Auth::id())->latest()->get();
        return view('recent-projects', compact('conversations'));
    }

    public function show(Conversation $conversation)
    {
        if ($conversation->user_id !== Auth::id()) {
            abort(403);
        }
        $conversation->load('document');
        return view('project-detail', compact('conversation'));
    }

    public function destroyProject(Conversation $conversation)
    {
        if ($conversation->user_id !== Auth::id()) {
            abort(403);
        }
        $conversation->delete();
        return redirect()->route('projects.history')->with('success', 'Project has been deleted successfully.');
    }

    // --- FITUR Q&A DENGAN DOKUMEN ---
    public function qnaIndex()
    {
        $documents = Document::where('user_id', Auth::id())->latest()->get();
        return view('qna.index', compact('documents'));
    }

    public function qnaUpload(Request $request)
    {
        $request->validate([
            'document' => 'required|file|mimes:pdf|max:10240', // 10MB
        ]);

        $file = $request->file('document');
        
        // Validate file
        $validation = $this->validateUploadedFile($file);
        if ($validation !== true) {
            return back()->with('error', $validation)->withInput();
        }

        try {
            // Parse PDF to extract text
            $parser = new \Smalot\PdfParser\Parser();
            $pdf = $parser->parseFile($file->getRealPath());
            $textContent = $pdf->getText();

            if (empty(trim($textContent))) {
                return back()
                    ->with('error', 'Could not extract text from PDF. File may be empty or corrupted.')
                    ->withInput();
            }

            // Store the actual PDF file
            $filePath = $file->store('documents', 'public');

            // Create document record with both content and file_path
            $document = Document::create([
                'user_id' => Auth::id(),
                'original_filename' => $file->getClientOriginalName(),
                'file_path' => $filePath,
                'content' => $textContent,
            ]);

            return redirect()->route('qna.chat', $document)
                ->with('success', 'Document uploaded successfully!');

        } catch (\Exception $e) {
            \Log::error('PDF upload error', ['error' => $e->getMessage()]);
            return back()
                ->with('error', 'Error processing PDF: ' . $e->getMessage())
                ->withInput();
        }
    }

    public function qnaChat(Document $document)
    {
        if ($document->user_id !== Auth::id()) {
            abort(403);
        }
        return view('qna.chat', compact('document'));
    }

    public function qnaAsk(Request $request, Document $document)
    {
        if ($document->user_id !== Auth::id()) {
            abort(403);
        }

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
        if ($document->user_id !== Auth::id()) {
            abort(403);
        }
        $document->delete();
        return redirect()->route('qna.index')->with('success', 'Document has been deleted successfully.');
    }

    // --- FITUR GENERATE FLASHCARD (SERVER-SIDE) ---
    public function generateFlashcards(Document $document)
    {
        if ($document->user_id !== Auth::id()) {
            abort(403);
        }

        set_time_limit(300);

        try {
            $context = Str::limit($document->content, 2500, '');
            // Prompt
            $prompt = "Berdasarkan teks berikut, buatlah satu set pertanyaan dan jawaban untuk flashcard. PENTING: Respons Anda HARUS HANYA berupa JSON array yang valid. Setiap objek harus memiliki kunci 'term' untuk pertanyaan dan 'definition' untuk jawaban. Jangan sertakan teks atau penjelasan lain sebelum atau sesudah JSON array. Buat antara 2 sampai 4 flashcard.\n\nContoh format: [{\"term\": \"Siapa nama tokoh utama?\", \"definition\": \"Arga.\"}]\n\nTeks:\n\"{$context}\"";

            // --- START OLLAMA MIGRATION ---
            $apiResponse = $this->callOllamaAPI($prompt);

            if (!$apiResponse->success) {
                throw new \Exception('The AI service failed to respond. Error: ' . $apiResponse->body);
            }

            $aiResponseContent = $apiResponse->text;
            // --- END OLLAMA MIGRATION ---


            // Logika pembersihan JSON ini SANGAT PENTING untuk OLLAMA/Model Open Source,
            // karena model mungkin masih membungkusnya dengan ```json
            $cleanedJsonString = trim(str_replace(['```json', '```'], '', $aiResponseContent));
            // Kadang model Qwen menambahkan teks pembuka, kita coba cari array JSON [ ... ]
            if (preg_match('/\[.*\]/s', $cleanedJsonString, $matches)) {
                $cleanedJsonString = $matches[0];
            }
            
            $flashcardsData = json_decode($cleanedJsonString, true);

            if (json_last_error() !== JSON_ERROR_NONE) {
                // Jika format JSON gagal, berikan pesan error yang lebih jelas
                throw new \Exception('The AI returned an invalid format. Response: ' . $cleanedJsonString);
            }

            $flashcardSet = FlashcardSet::create([
                'user_id' => Auth::id(), // Penting
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
     * HELPER METHOD BARU UNTUK OLLAMA API (SENOPATI)
     * =================================================================
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

        // Endpoint Senopati biasanya /generate atau /api/generate
        // Cek dokumentasi teman/kampus Anda. Default Ollama adalah /api/generate
        // Tapi jika URL teman Anda berakhiran slash, kita sesuaikan.
        $apiUrl = rtrim($apiUrlBase, '/') . '/generate'; 
        
        $model = env('OLLAMA_MODEL', 'qwen2.5:14b');
        $temperature = (float) env('OLLAMA_TEMPERATURE', 0.1);

        // 2. Susun payload sesuai standar OLLAMA
        $payload = [
            'model' => $model,
            'prompt' => $prompt,
            'temperature' => $temperature,
            'stream' => false, // Kita matikan streaming agar mudah diolah
        ];

        // 3. (PENTING) Tambahkan 'format: json' jika prompt meminta JSON
        if (str_contains(strtoupper($prompt), 'JSON')) {
            $payload['format'] = 'json';
        }

        // 4. Lakukan panggilan API
        // Tidak perlu WithToken karena Senopati biasanya IP-based atau internal network
        // Jika butuh token, tambahkan ->withToken('...')
        $response = Http::timeout(180)->post($apiUrl, $payload);

        // 5. Tangani kegagalan
        if ($response->failed()) {
            return (object)['success' => false, 'body' => $response->body()];
        }

        // 6. Parsing respons OLLAMA
        // Respons Ollama ada di key 'response'
        $responseText = $response->json('response');

        if ($responseText === null) {
             return (object)[
                'success' => false,
                'body' => 'Invalid or empty response from OLLAMA. Full response: ' . $response->body()
            ];
        }

        return (object)['success' => true, 'text' => $responseText];
    }

    /**
     * Validate uploaded file
     */
    private function validateUploadedFile($file): bool|string
    {
        if (!$file) {
            return 'No file provided.';
        }

        if (!$file->isValid()) {
            return 'File upload failed. Error: ' . $file->getErrorMessage();
        }

        $size = $file->getSize();
        if ($size > self::MAX_FILE_SIZE) {
            $maxMB = self::MAX_FILE_SIZE / (1024 * 1024);
            return "File size ({$size} bytes) exceeds {$maxMB}MB limit.";
        }

        if ($size === 0) {
            return 'File is empty.';
        }

        $extension = strtolower($file->getClientOriginalExtension());
        if (!in_array($extension, self::SUPPORTED_FORMATS)) {
            return "File type '.{$extension}' is not supported. Only PDF files are allowed.";
        }

        $mimeType = $file->getMimeType();
        if ($mimeType !== 'application/pdf') {
            return "Invalid file type '{$mimeType}'. Please upload a valid PDF file.";
        }

        return true;
    }

    public function uploadChunk(Request $request)
    {
        // Validate chunk size (5MB per chunk)
        $request->validate([
            'chunk' => 'required|file|max:5120', // 5MB
            'chunk_index' => 'required|integer',
            'total_chunks' => 'required|integer',
            'file_id' => 'required|string',
            'filename' => 'required|string|max:500',
        ]);

        $totalChunks = $request->input('total_chunks');
        $fileId = $request->input('file_id');
        
        // Calculate total file size from chunks
        $chunkIndex = $request->input('chunk_index');
        $tempDir = storage_path('app/chunks/' . $fileId);
        $totalSize = 0;
        
        // Check if total size would exceed 10MB
        for ($i = 0; $i < $totalChunks; $i++) {
            $chunkPath = "{$tempDir}/chunk_{$i}";
            if (file_exists($chunkPath)) {
                $totalSize += filesize($chunkPath);
            }
        }
        
        if ($totalSize > self::MAX_FILE_SIZE) {
            return response()->json(['success' => false, 'error' => 'Total file size exceeds 10MB limit'], 413);
        }

        // Create temp directory for chunks
        $tempDir = storage_path('app/chunks/' . $fileId);
        if (!is_dir($tempDir)) {
            mkdir($tempDir, 0755, true);
        }

        // Save chunk
        $chunk->move($tempDir, "chunk_{$chunkIndex}");

        // Check if all chunks are uploaded
        $uploadedChunks = 0;
        for ($i = 0; $i < $totalChunks; $i++) {
            if (file_exists("{$tempDir}/chunk_{$i}")) {
                $uploadedChunks++;
            }
        }

        // If all chunks uploaded, merge them
        if ($uploadedChunks === $totalChunks) {
            try {
                $mergedFilePath = storage_path('app/chunks/' . $fileId . '_merged.pdf');
                $mergedFile = fopen($mergedFilePath, 'wb');

                for ($i = 0; $i < $totalChunks; $i++) {
                    $chunkPath = "{$tempDir}/chunk_{$i}";
                    $chunk = fopen($chunkPath, 'rb');
                    stream_copy_to_stream($chunk, $mergedFile);
                    fclose($chunk);
                    unlink($chunkPath);
                }
                fclose($mergedFile);

                // Process the merged PDF
                return $this->processMergedPDF($mergedFilePath, $filename);

            } catch (\Exception $e) {
                \Log::error('Chunk merge error', ['error' => $e->getMessage()]);
                return response()->json(['success' => false, 'error' => 'Error merging chunks'], 500);
            }
        }

        return response()->json([
            'success' => true,
            'message' => "Chunk {$chunkIndex} uploaded",
            'uploaded_chunks' => $uploadedChunks,
        ]);
    }

    private function processMergedPDF($mergedFilePath, $filename)
    {
        try {
            // Parse PDF
            $parser = new \Smalot\PdfParser\Parser();
            $pdf = $parser->parseFile($mergedFilePath);
            $textContent = $pdf->getText();

            if (empty(trim($textContent))) {
                unlink($mergedFilePath);
                return response()->json(['success' => false, 'error' => 'Could not extract text from PDF'], 400);
            }

            // Move to final location
            $filePath = 'documents/' . uniqid() . '.pdf';
            rename($mergedFilePath, storage_path('app/public/' . $filePath));

            // Create document record
            $document = Document::create([
                'user_id' => Auth::id(),
                'original_filename' => $filename,
                'file_path' => $filePath,
                'content' => $textContent,
            ]);

            // Clean up temp directory
            $tempDir = storage_path('app/chunks/' . pathinfo($mergedFilePath, PATHINFO_FILENAME));
            if (is_dir($tempDir)) {
                rmdir($tempDir);
            }

            return response()->json([
                'success' => true,
                'document_id' => $document->id,
                'message' => 'File uploaded and processed successfully',
            ]);

        } catch (\Exception $e) {
            \Log::error('PDF processing error', ['error' => $e->getMessage()]);
            if (file_exists($mergedFilePath)) {
                unlink($mergedFilePath);
            }
            return response()->json(['success' => false, 'error' => $e->getMessage()], 500);
        }
    }
}