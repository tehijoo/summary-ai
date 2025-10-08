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
            'temperature' => 0.2,
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
}
