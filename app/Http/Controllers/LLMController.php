<?php

namespace App\Http\Controllers;

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

        $document = Document::create([
            'original_filename' => $originalName,
            'content' => $textContent,
        ]);

        $apiKey = env('GEMINI_API_KEY');
        $apiUrl = "https://generativelanguage.googleapis.com/v1beta/models/gemini-2.5-flash:generateContent?key={$apiKey}";
        $prompt = "Anda adalah seorang ahli yang pandai membuat ringkasan teks. Tolong buatkan ringkasan dari teks berikut. Berikan ringkasan dengan bahasa yang baik dan jelas dan memuat poin dari teks:\n\n" . Str::limit($textContent, 30000, '');

        $response = Http::timeout(180)->post($apiUrl, [
            'contents' => [['parts' => [['text' => $prompt]]]]
        ]);

        if ($response->failed()) {
            return back()->with('response', 'Failed to connect to the Gemini API. Error: ' . $response->body());
        }

        $summaryMarkdown = $response->json('candidates.0.content.parts.0.text') ?? 'No valid response from model.';
        $parsedown = new \Parsedown();
        $summaryHtml = $parsedown->text($summaryMarkdown);

        Conversation::create([
            'document_id' => $document->id,
            'mode' => 'summarize',
            'input' => $textContent,
            'response' => $summaryMarkdown,
        ]);

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

        $apiKey = env('GEMINI_API_KEY');
        $apiUrl = "https://generativelanguage.googleapis.com/v1beta/models/gemini-2.5-flash:generateContent?key={$apiKey}";
        $prompt = "Based on the following text, answer the question.\n\nText:\n\"{$context}\"\n\nQuestion: {$question}\n\nAnswer in Indonesian:";
        
        $response = Http::timeout(180)->post($apiUrl, [
            'contents' => [['parts' => [['text' => $prompt]]]]
        ]);

        if ($response->failed()) {
            return response()->json(['answer' => 'Sorry, I failed to connect to the Gemini API. Error: ' . $response->body()]);
        }

        $answer = $response->json('candidates.0.content.parts.0.text') ?? 'Sorry, I could not find an answer.';
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
            $prompt = "Berdasarkan teks berikut, buatlah satu set pertanyaan dan jawaban untuk flashcard. PENTING: Respons Anda HARUS HANYA berupa JSON array yang valid. Setiap objek harus memiliki kunci 'term' untuk pertanyaan dan 'definition' untuk jawaban. Jangan sertakan teks atau penjelasan lain sebelum atau sesudah JSON array. Buat antara 2 sampai 4 flashcard.\n\nContoh format: [{\"term\": \"Siapa nama tokoh utama?\", \"definition\": \"Arga.\"}]\n\nTeks:\n\"{$context}\"";
            
            $apiKey = env('GEMINI_API_KEY');
            // Gunakan model Pro yang lebih kuat untuk tugas JSON
            $apiUrl = "https://generativelanguage.googleapis.com/v1beta/models/gemini-2.5-pro:generateContent?key={$apiKey}";

            $response = Http::timeout(180)->post($apiUrl, [
                'contents' => [['parts' => [['text' => $prompt]]]]
            ]);

            if ($response->failed()) {
                throw new \Exception('The AI service failed to respond.');
            }

            $aiResponseContent = $response->json('candidates.0.content.parts.0.text');
            $cleanedJsonString = trim(str_replace(['```json', '```'], '', $aiResponseContent));
            $flashcardsData = json_decode($cleanedJsonString, true);

            if (json_last_error() !== JSON_ERROR_NONE) {
                throw new \Exception('The AI returned an invalid format.');
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
}