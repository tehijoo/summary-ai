@extends('layouts.app')

@section('content')
<div class="max-w-4xl mx-auto">
    {{-- Menampilkan pesan sukses/error dari sesi --}}
    @if(session('success'))
        <div class="mb-6 p-4 text-sm text-green-800 rounded-lg bg-green-50 dark:bg-surface-dark dark:text-green-400 border border-green-200 dark:border-green-600" role="alert">
            {{ session('success') }}
        </div>
    @endif
    @if(session('error'))
        <div class="mb-6 p-4 text-sm text-red-800 rounded-lg bg-red-50 dark:bg-surface-dark dark:text-red-400 border border-red-200 dark:border-red-600" role="alert">
            {{ session('error') }}
        </div>
    @endif

    <div class="flex flex-wrap justify-between items-center mb-8 gap-4">
        {{-- Tombol Kembali --}}
        <a href="{{ route('projects.history') }}" class="flex items-center text-text-light-secondary dark:text-dark-secondary hover:text-primary dark:hover:text-primary transition-colors">
            <span class="material-icons mr-2">arrow_back</span>
            Back to Recent Projects
        </a>

        {{-- KONTENER UNTUK TOMBOL AKSI --}}
        @if($conversation->document)
            <div class="flex items-center gap-4">
                {{-- Tombol Q&A --}}
                <a href="{{ route('qna.chat', $conversation->document) }}" class="bg-primary text-white font-bold py-2 px-6 rounded-lg hover:bg-primary/90 transition-colors flex items-center">
                    <span class="material-icons mr-2">quiz</span>
                    Start Q&A
                </a>

                {{-- Tombol Generate Flashcards --}}
                <button id="generate-flashcards-btn" class="bg-surface-light dark:bg-surface-dark border border-border-light dark:border-border-dark text-text-light-primary dark:text-dark-primary font-bold py-2 px-6 rounded-lg hover:border-primary dark:hover:border-primary transition-colors flex items-center">
                    <span id="btn-icon" class="material-icons mr-2 text-primary">auto_awesome</span>
                    <span id="btn-text">Generate Flashcards</span>
                </button>
            </div>
        @endif
    </div>

    {{-- Tampilan Detail Ringkasan --}}
    <div class="bg-surface-light dark:bg-surface-dark p-8 rounded-lg shadow-sm border border-border-light dark:border-border-dark">
        <p class="text-sm text-text-light-secondary dark:text-dark-secondary mb-4">
            Created on: {{ $conversation->created_at->format('d M Y, h:i A') }}
        </p>
        <h3 class="text-2xl font-bold text-text-light-primary dark:text-dark-primary mb-6">Summary</h3>
        <div class="prose dark:prose-invert max-w-none" style="white-space: pre-wrap;">
            {!! $conversation->response !!}
        </div>
        <hr class="my-6 border-border-light dark:border-border-dark">
        <h3 class="text-2xl font-bold text-text-light-primary dark:text-dark-primary mb-6">Original Input</h3>
        <div class="prose dark:prose-invert max-w-none bg-background-light dark:bg-background-dark p-4 rounded" style="white-space: pre-wrap; max-height: 400px; overflow-y: auto;">
            {{ $conversation->input }}
        </div>
    </div>
</div>
@endsection

@section('scripts')
<script>
document.addEventListener('DOMContentLoaded', function () {
    const generateBtn = document.getElementById('generate-flashcards-btn');
    if (!generateBtn) return;

    const btnIcon = document.getElementById('btn-icon');
    const btnText = document.getElementById('btn-text');

    const context = @json(Str::limit($conversation->document->content, 2500, ''));
    const docTitle = @json('AI Flashcards for: ' . Str::limit($conversation->document->original_filename, 50));
    const docDesc = @json('Automatically generated from a document.');

    function extractJson(text) {
        const startIndex = text.indexOf('[');
        const endIndex = text.lastIndexOf(']');
        if (startIndex === -1 || endIndex === -1) return null;
        return text.substring(startIndex, endIndex + 1);
    }

    generateBtn.addEventListener('click', async function() {
        this.disabled = true;
        btnIcon.textContent = 'hourglass_top';
        btnIcon.classList.add('animate-spin');
        btnText.textContent = 'AI is Generating...';

        const prompt = `Based on the text below, create a set of questions and answers for flashcards. IMPORTANT: Your response MUST be ONLY a valid JSON array of objects. Each object must have a 'term' key for the question and a 'definition' key for the answer. Do not include any other text or explanations before or after the JSON array. Create between 2 to 4 flashcards.\n\nText:\n\"${context}\"`;

        try {
            const mistralResponse = await fetch('{{ env("LLM_API_URL") }}', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'Authorization': `Bearer {{ env('MISTRAL_API_KEY') }}`
                },
                body: JSON.stringify({
                    model: 'mistral-small-latest',
                    messages: [
                        { role: 'system', content: 'Anda adalah asisten yang membantu membuat flashcard dalam format JSON.' },
                        { role: 'user', content: prompt }
                    ],
                    temperature: 0.5,
                    max_tokens: 1500,
                })
            });

            if (!mistralResponse.ok) throw new Error('Failed to get response from Mistral API.');
            
            const mistralData = await mistralResponse.json();
            let aiContent = mistralData.choices[0].message.content;
            
            const jsonString = extractJson(aiContent);
            if (!jsonString) throw new Error('Valid JSON block not found in AI response.');
            
            const flashcardsData = JSON.parse(jsonString);

            // KUNCI PERBAIKAN #2: Gunakan variabel JavaScript yang aman
            const laravelResponse = await fetch('{{ route("flashcards.ai-save") }}', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'X-CSRF-TOKEN': '{{ csrf_token() }}'
                },
                body: JSON.stringify({
                    title: docTitle,
                    description: docDesc,
                    flashcardsData: flashcardsData
                })
            });

            if (!laravelResponse.ok) throw new Error('Failed to save flashcards to the server.');

            const laravelData = await laravelResponse.json();
            window.location.href = laravelData.redirect_url;

        } catch (error) {
            console.error('Error:', error);
            alert('An error occurred while generating flashcards. The AI may have returned an invalid format. Please try again.');
            this.disabled = false;
            btnIcon.textContent = 'auto_awesome';
            btnIcon.classList.remove('animate-spin');
            btnText.textContent = 'Generate Flashcards';
        }
    });
});
</script>
@endsection