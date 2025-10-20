<?php

namespace App\Http\Controllers;

use App\Models\Conversation;
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
        $originalName = 'Pasted Text'; // Default filename for pasted text

        // 1. Extract text if a PDF file is uploaded
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

        // Validate if there is no text at all
        if (!$textContent) {
            return back()->with('response', 'Please enter text or upload a PDF.');
        }

        $textContent = preg_replace('/\s+/', ' ', trim($textContent));

        // 2. Save the original content to the 'documents' table
        $document = Document::create([
            'original_filename' => $originalName,
            'content' => $textContent,
        ]);

        // --- CHANGE HERE: Updated model and API version ---
        $apiKey = env('GEMINI_API_KEY');
        $apiUrl = "https://generativelanguage.googleapis.com/v1beta/models/gemini-2.5-flash:generateContent?key={$apiKey}";

        $prompt = "Anda adalah seorang ahli yang pandai membuat ringkasan teks. Tolong buatkan ringkasan dari teks berikut. Berikan ringkasan dengan bahasa yang baik dan jelas dan memuat poin dari teks:\n\n" . Str::limit($textContent, 30000, '');

        $response = Http::post($apiUrl, [
            'contents' => [
                [
                    'parts' => [
                        ['text' => $prompt]
                    ]
                ]
            ]
        ]);

        if ($response->failed()) {
            return back()->with('response', 'Failed to connect to the Gemini API. Error: ' . $response->body());
        }

        // Parse the Gemini API response
        $summaryMarkdown = $response->json('candidates.0.content.parts.0.text') ?? 'No valid response from model.';
        
        // Process Markdown into HTML
        $parsedown = new \Parsedown();
        $summaryHtml = $parsedown->text($summaryMarkdown);

        // 4. Save the summary to the 'conversations' table AND link it to the document
        Conversation::create([
            'document_id' => $document->id, // Link this summary to the original document
            'mode' => 'summarize',
            'input' => $textContent, // Input is now the full document content
            'response' => $summaryMarkdown,
        ]);

        return redirect('/chat')->with('response', $summaryHtml)->withInput();
    }
    // Method to display recent projects
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
    // --- NEW METHOD FOR Q&A FEATURE ---

    public function qnaIndex()
    {
        $documents = \App\Models\Document::latest()->get();
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

        $document = \App\Models\Document::create([
            'original_filename' => $originalName,
            'content' => $textContent,
        ]);

        return redirect()->route('qna.chat', $document);
    }

    public function qnaChat(\App\Models\Document $document)
    {
        return view('qna.chat', compact('document'));
    }

    public function qnaAsk(Request $request, \App\Models\Document $document)
    {
        $request->validate(['question' => 'required|string']);

        $question = $request->question;
        $context = $document->content;
        $context = Str::limit($context, 30000, '');

        // --- CHANGE HERE: Updated model and API version ---
        $apiKey = env('GEMINI_API_KEY');
        $apiUrl = "https://generativelanguage.googleapis.com/v1beta/models/gemini-2.5-flash:generateContent?key={$apiKey}";

        $prompt = "You are a helpful assistant that answers questions based on the provided text. Based on the following text, answer the question.\n\nText:\n\"{$context}\"\n\nQuestion: {$question}\n\nAnswer in Indonesian:";

        $response = Http::post($apiUrl, [
            'contents' => [
                [
                    'parts' => [
                        ['text' => $prompt]
                    ]
                ]
            ]
        ]);

        if ($response->failed()) {
            return response()->json(['answer' => 'Sorry, I failed to connect to the Gemini API. Error: ' . $response->body()]);
        }

        $answer = $response->json('candidates.0.content.parts.0.text') ?? 'Sorry, I could not find an answer.';
        return response()->json(['answer' => $answer]);
    }
    public function destroyProject(\App\Models\Conversation $conversation)
    {
        $conversation->delete();
        return redirect()->route('projects.history')->with('success', 'Project has been deleted successfully.');
    }

    /*
    public function generateFlashcards(Document $document)
    {
        set_time_limit(300);

        $context = Str::limit($document->content, 25000, ''); 
        $prompt = "Berdasarkan teks berikut, buatlah satu set pertanyaan dan jawaban untuk flashcard. Berikan output dalam format JSON array yang valid. Setiap objek harus memiliki kunci 'term' untuk pertanyaan dan 'definition' untuk jawaban. Buat antara 1 sampai 3 flashcard.\n\nContoh format: [{\"term\": \"Siapa nama tokoh utama?\", \"definition\": \"Arga.\"}]\n\nTeks:\n\"{$context}\"";

        // --- CHANGE HERE: Updated model and API version ---
        $apiKey = env('GEMINI_API_KEY');
        $apiUrl = "https://generativelanguage.googleapis.com/v1beta/models/gemini-2.5-flash:generateContent?key={$apiKey}";

        $response = Http::timeout(180)->post($apiUrl, [
            'contents' => [
                [
                    'parts' => [
                        ['text' => $prompt]
                    ]
                ]
            ]
        ]);
        
        if ($response->failed()) {
            return back()->with('error', 'The AI service timed out or failed. Please try again. Error: ' . $response->body());
        }

        $aiResponseContent = $response->json('candidates.0.content.parts.0.text');
        $cleanedJsonString = trim(str_replace(['```json', '```'], '', $aiResponseContent));

        try {
            $flashcardsData = json_decode($cleanedJsonString, true, 512, JSON_THROW_ON_ERROR);
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

        $flashcardSet = FlashcardSet::create([
            'title' => $request->title,
            'description' => $request->description,
        ]);

        foreach ($request->flashcardsData as $cardData) {
            if (isset($cardData['term']) && isset($cardData['definition'])) {
                $flashcardSet->flashcards()->create([
                    'term' => $cardData['term'],
                    'definition' => $cardData['definition'],
                ]);
            }
        }

        return response()->json([
            'redirect_url' => route('flashcards.show', $flashcardSet)
        ]);
    }
    public function destroyDocument(\App\Models\Document $document)
    {
        $document->delete();
        return redirect()->route('qna.index')->with('success', 'Document has been deleted successfully.');
    }

    // ADD THIS ENTIRE NEW METHOD
    public function generateFlashcardsFromDocument(\App\Models\Document $document)
    {
        try {
            // Get limited context from the document
            $context = Str::limit($document->content, 25000, ''); 
            
            // Construct the prompt for the Gemini API
            $prompt = "Based on the text below, create a set of questions and answers for flashcards. IMPORTANT: Your response MUST be ONLY a valid JSON array of objects. Each object must have a 'term' key for the question and a 'definition' key for the answer. Do not include any other text or explanations before or after the JSON array. Create between 2 to 4 flashcards.\n\nText:\n\"{$context}\"";

            // Securely call the Gemini API from the server
            $apiKey = env('GEMINI_API_KEY');
            $apiUrl = "https://generativelanguage.googleapis.com/v1beta/models/gemini-1.5-pro-latest:generateContent?key={$apiKey}";

            $response = Http::timeout(180)->post($apiUrl, [
                'contents' => [
                    [
                        'parts' => [
                            ['text' => $prompt]
                        ]
                    ]
                ]
            ]);
            
            if ($response->failed()) {
                // If the API call fails, return an error
                return response()->json(['error' => 'The AI service failed to respond.'], 500);
            }

            // Clean up the response from Gemini
            $aiResponseContent = $response->json('candidates.0.content.parts.0.text');
            $cleanedJsonString = trim(str_replace(['```json', '```'], '', $aiResponseContent));

            // Try to decode the JSON
            $flashcardsData = json_decode($cleanedJsonString, true);

            // Check if the JSON is valid
            if (json_last_error() !== JSON_ERROR_NONE) {
                 return response()->json(['error' => 'The AI returned an invalid format.'], 500);
            }

            // If everything is successful, return the flashcard data
            return response()->json($flashcardsData);

        } catch (\Exception $e) {
            // Catch any other unexpected errors
            return response()->json(['error' => $e->getMessage()], 500);
        }
    }
}