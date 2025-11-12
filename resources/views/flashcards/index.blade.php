@extends('layouts.app')

@section('content')
{{-- Menggunakan padding konsisten dari layout utama (app.blade.php) --}}
<div>
    {{-- ======================================================= --}}
    {{-- ## KODE HEADER BARU (PERBAIKAN) ## --}}
    {{-- ======================================================= --}}
    
    {{-- Judul dan Subjudul (dibuat terpusat) --}}
    <div class="text-center mb-12">
        <h2 class="text-4xl font-bold text-gray-900 dark:text-white mb-3">Flashcard Sets 🎴</h2>
        <p class="text-lg text-gray-500 dark:text-gray-400">Browse your created flashcard sets.</p>
    </div>

    {{-- Tombol "Create New Set" (diletakkan di kanan) --}}
    <div class="flex justify-end mb-6">
        <a href="{{ route('flashcards.create') }}" class="bg-primary text-white font-bold py-3 px-6 rounded-lg hover:bg-blue-600 transition-colors duration-300 text-base flex items-center">
            <span class="material-icons-outlined mr-2">add</span>
            <span>Create New Set</span>
        </a>
    </div>

    {{-- ======================================================= --}}
    {{-- ## BATAS AKHIR PERBAIKAN ## --}}
    {{-- ======================================================= --}}


    {{-- Menampilkan pesan sukses setelah menghapus --}}
    @if(session('success'))
        <div class="mb-6 p-4 text-sm text-green-800 rounded-lg bg-green-50 dark:bg-surface-dark dark:text-green-400 border border-green-200 dark:border-green-600" role="alert">
            {{ session('success') }}
        </div>
    @endif

    @if($flashcardSets->count() > 0)
        <div class="grid grid-cols-1 sm:grid-cols-2 md:grid-cols-3 lg:grid-cols-4 gap-6">
            @foreach($flashcardSets as $set)
            {{-- Kontainer kartu --}}
            <div class="relative flex flex-col bg-surface-light dark:bg-surface-dark p-6 rounded-xl shadow-md dark:shadow-lg dark:shadow-primary/10 border border-border-light dark:border-border-dark hover:border-primary dark:hover:border-primary transition-all duration-300">
                
                <form action="{{ route('flashcards.destroy', $set) }}" method="POST" class="delete-form absolute top-3 right-3 z-10">
                    @csrf
                    @method('DELETE')
                    <button type="submit" class="p-1 rounded-full text-text-light-secondary/50 dark:text-dark-secondary/50 hover:bg-red-100 dark:hover:bg-red-900/50 hover:text-red-600 dark:hover:text-red-400 transition-colors">
                        <span class="material-icons-outlined text-base">delete</span>
                    </button>
                </form>

                {{-- Konten kartu yang bisa diklik --}}
                <a href="{{ route('flashcards.show', $set) }}" class="flex flex-col flex-grow h-full">
                    <h4 class="font-bold text-lg text-text-light-primary dark:text-dark-primary">
                        {{ $set->title }}
                    </h4>
                    <p class="text-sm text-text-light-secondary dark:text-dark-secondary mt-2 flex-grow">
                        {{ Str::limit($set->description, 60) }}
                    </D>
                    <div class="mt-4 text-sm font-medium text-text-light-secondary dark:text-dark-secondary flex items-center">
                        <span class="material-icons-outlined mr-2 text-base">style</span>
                        <span>{{ $set->flashcards_count }} Cards</span>
                    </div>
                </a>
            </div>
            @endforeach
        </div>
    @else
        {{-- Pesan jika kosong --}}
        <div class="text-center bg-surface-light dark:bg-surface-dark p-12 rounded-xl border border-border-light dark:border-border-dark">
            <span class="material-icons-outlined text-6xl text-text-light-secondary dark:text-dark-secondary">search_off</span>
            <h3 class="mt-4 text-xl font-bold text-text-light-primary dark:text-dark-primary">No Flashcard Sets Found 🤷‍♂️</h3>
            <p class="mt-2 text-text-light-secondary dark:text-dark-secondary">Get started by creating a new set.</p>
        </div>
    @endif
</div>
@endsection

@section('scripts')
{{-- Skrip konfirmasi hapus --}}
<script>
    document.addEventListener('DOMContentLoaded', function () {
        document.querySelectorAll('.delete-form').forEach(form => {
            form.addEventListener('submit', function (event) {
                if (!confirm('Are you sure you want to delete this flashcard set?')) {
                    event.preventDefault();
                }
            });
        });
    });
</script>
@endsection