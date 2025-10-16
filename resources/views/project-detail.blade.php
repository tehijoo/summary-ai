@extends('layouts.app')

@section('content')
<div class="max-w-4xl mx-auto">
    {{-- Menampilkan pesan sukses setelah generate flashcard --}}
    @if(session('success'))
        <div class="mb-6 p-4 text-sm text-green-800 rounded-lg bg-green-50 dark:bg-surface-dark dark:text-green-400 border border-green-200 dark:border-green-600" role="alert">
            {{ session('success') }}
        </div>
    @endif

    {{-- Menampilkan pesan error jika AI gagal --}}
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
                {{-- Tombol Q&A yang sudah ada --}}
                <a href="{{ route('qna.chat', $conversation->document) }}" class="bg-primary text-white font-bold py-2 px-6 rounded-lg hover:bg-primary/90 transition-colors flex items-center">
                    <span class="material-icons mr-2">quiz</span>
                    Start Q&A
                </a>

                {{-- ============================================= --}}
                {{--         TOMBOL BARU DITAMBAHKAN DI SINI        --}}
                {{-- ============================================= --}}
                <form action="{{ route('documents.generate-flashcards', $conversation->document) }}" method="POST">
                    @csrf
                    <button type="submit" class="bg-surface-light dark:bg-surface-dark border border-border-light dark:border-border-dark text-text-light-primary dark:text-dark-primary font-bold py-2 px-6 rounded-lg hover:border-primary dark:hover:border-primary transition-colors flex items-center">
                        <span class="material-icons mr-2 text-primary">auto_awesome</span>
                        Generate Flashcards
                    </button>
                </form>
            </div>
        @endif
    </div>

    <div class="bg-surface-light dark:bg-surface-dark p-8 rounded-lg shadow-sm border border-border-light dark:border-border-dark">
        <p class="text-sm text-text-light-secondary dark:text-dark-secondary mb-4">
            Created on: {{ $conversation->created_at->format('d M Y, h:i A') }}
        </p>

        <h3 class="text-2xl font-bold text-text-light-primary dark:text-dark-primary mb-6">Summary</h3>
        <div class="prose dark:prose-invert max-w-none" style="white-space: pre-wrap;">
            {{ $conversation->response }}
        </div>

        <hr class="my-6 border-border-light dark:border-border-dark">

        <h3 class="text-2xl font-bold text-text-light-primary dark:text-dark-primary mb-6">Original Input</h3>
        <div class="prose dark:prose-invert max-w-none bg-background-light dark:bg-background-dark p-4 rounded" style="white-space: pre-wrap; max-height: 400px; overflow-y: auto;">
            {{ $conversation->input }}
        </div>
    </div>
</div>
@endsection