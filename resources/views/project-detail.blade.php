@extends('layouts.app')

@section('content')
<div class="container mx-auto px-6 py-8">
    {{-- Notifikasi --}}
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

    {{-- Loading Overlay --}}
    <div id="loadingOverlay" class="fixed inset-0 bg-black/50 flex items-center justify-center z-50" style="display: none;">
        <div class="bg-white dark:bg-surface-dark p-8 rounded-xl shadow-lg text-center">
            <div class="mb-4">
                <div class="inline-block w-12 h-12 border-4 border-gray-300 dark:border-gray-600 border-t-primary rounded-full animate-spin"></div>
            </div>
            <p class="text-text-light-primary dark:text-dark-primary font-semibold">Generating Flashcards...</p>
        </div>
    </div>

    <div class="flex flex-wrap justify-between items-center mb-8 gap-4">
        {{-- Tombol Kembali --}}
        <a href="{{ route('projects.history') }}" class="flex items-center text-text-light-secondary dark:text-dark-secondary hover:text-primary dark:hover:text-primary transition-colors">
            <span class="material-icons-outlined mr-2">arrow_back</span>
            <span>Back to Recent Projects</span>
        </a>

        {{-- Tombol Aksi --}}
        @if($conversation->document)
            <div class="flex items-center gap-4">
                <a href="{{ route('qna.chat', $conversation->document) }}" class="bg-primary text-white font-bold py-2.5 px-6 rounded-lg hover:bg-blue-600 transition-colors duration-300 flex items-center">
                    <span class="material-icons-outlined mr-2">quiz</span>
                    <span>Start Q&A 💬</span>
                </a>
                
                <form id="generateFlashcardsForm" action="{{ route('documents.generate-flashcards', $conversation->document) }}" method="POST">
                    @csrf
                    <button type="submit" id="generateBtn" class="bg-surface-light dark:bg-surface-dark border border-border-light dark:border-border-dark text-text-light-primary dark:text-dark-primary font-bold py-2.5 px-6 rounded-lg hover:border-primary dark:hover:border-primary transition-colors duration-300 flex items-center">
                        <span class="material-icons-outlined mr-2 text-primary">auto_awesome</span>
                        <span id="generateText">Generate Flashcards ✨</span>
                        <span id="generateSpinner" class="ml-2" style="display: none;">
                            <div class="inline-block w-4 h-4 border-2 border-primary border-t-transparent rounded-full animate-spin"></div>
                        </span>
                    </button>
                </form>
            </div>
        @endif
    </div>

    {{-- Tampilan Detail Ringkasan --}}
    <div class="bg-surface-light dark:bg-surface-dark p-8 rounded-xl border border-border-light dark:border-border-dark">
        <p class="text-sm text-text-light-secondary dark:text-dark-secondary mb-4">
            Created on: {{ $conversation->created_at->format('d M Y, h:i A') }}
        </p>
        <h3 class="text-3xl font-bold text-text-light-primary dark:text-dark-primary mb-6">Summary 📝</h3>
        <div class="prose dark:prose-invert max-w-none text-text-light-primary dark:text-gray-200" style="white-space: pre-wrap;">
            {!! $conversation->response !!}
        </div>
        <hr class="my-6 border-border-light dark:border-border-dark">
        <h3 class="text-3xl font-bold text-text-light-primary dark:text-dark-primary mb-6">Original Input 📄</h3>
        <div class="prose dark:prose-invert max-w-none bg-background-light dark:bg-background-dark p-4 rounded-lg text-text-light-primary dark:text-gray-200" style="white-space: pre-wrap; max-height: 400px; overflow-y: auto;">
            {{ $conversation->input }}
        </div>
    </div>
</div>

<script>
    document.getElementById('generateFlashcardsForm').addEventListener('submit', function(e) {
        // Show loading overlay
        document.getElementById('loadingOverlay').style.display = 'flex';
        
        // Hide button text and show spinner
        document.getElementById('generateText').style.display = 'none';
        document.getElementById('generateSpinner').style.display = 'inline';
        
        // Disable the button to prevent multiple submissions
        document.getElementById('generateBtn').disabled = true;
    });
</script>
@endsection