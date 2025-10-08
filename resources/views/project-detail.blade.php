@extends('layouts.app')

@section('content')
<div class="max-w-4xl mx-auto">
    <div class="mb-8">
        {{-- Tombol Kembali --}}
        <a href="{{ route('projects.history') }}" class="flex items-center text-text-light-secondary dark:text-dark-secondary hover:text-primary dark:hover:text-primary transition-colors">
            <span class="material-icons mr-2">arrow_back</span>
            Back to Recent Projects
        </a>
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