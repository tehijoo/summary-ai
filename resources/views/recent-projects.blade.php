@extends('layouts.app')

@section('content')
<div class="max-w-6xl mx-auto">
    <div class="mb-12">
        {{-- Perubahan: Tambah warna teks untuk light/dark mode --}}
        <h2 class="text-3xl font-bold text-gray-800 dark:text-white">Recent Projects</h2>
        <p class="text-gray-500 dark:text-gray-300 mt-2">Here is a list of your past summaries.</p>
    </div>

    @if(isset($conversations) && $conversations->count() > 0)
        <div class="grid grid-cols-1 sm:grid-cols-2 md:grid-cols-3 lg:grid-cols-4 gap-6">
            @foreach($conversations as $c)
            {{-- Perubahan: Tambah warna latar, border, dan teks untuk dark mode --}}
            <a href="{{ route('projects.show', $c->id) }}" class="block bg-white dark:bg-gray-800 p-6 rounded-lg shadow-sm border dark:border-gray-700 hover:border-blue-600 dark:hover:border-blue-500 transition-all duration-300">
                <div class="flex items-start">
                    <span class="material-icons text-blue-600 dark:text-blue-400 mr-4 mt-1">description</span>
                    <div>
                        <h4 class="font-bold text-gray-800 dark:text-white">
                            {{ Str::words($c->input, 5, '...') }}
                        </h4>
                        <p class="text-sm text-gray-500 dark:text-gray-400 mt-1">
                            {{ $c->created_at->diffForHumans() }}
                        </p>
                    </div>
                </div>
            </a>
            @endforeach
        </div>
    @else
        <p class="text-gray-500 dark:text-gray-400">No conversation history yet.</p>
    @endif
</div>
@endsection