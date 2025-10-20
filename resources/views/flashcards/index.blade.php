@extends('layouts.app')

@section('content')

<div class="max-w-6xl mx-auto">
<div class="flex justify-between items-center mb-12">
<div>
<h2 class="text-3xl font-bold text-gray-800 dark:text-white">Flashcard Sets</h2>
<p class="text-gray-500 dark:text-gray-300 mt-2">Browse your created flashcard sets.</p>
</div>
<a href="{{ route('flashcards.create') }}" class="bg-blue-600 text-white font-bold py-3 px-6 rounded-lg hover:bg-blue-700 transition-colors text-base">
<span class="flex items-center"><span class="material-icons mr-2">add</span>Create New Set</span>
</a>
</div>

{{-- Menampilkan pesan sukses setelah menghapus --}}
@if(session('success'))
    <div class="mb-6 p-4 text-sm text-green-800 rounded-lg bg-green-50 dark:bg-surface-dark dark:text-green-400 border border-green-200 dark:border-green-600" role="alert">
        {{ session('success') }}
    </div>
@endif

@if($flashcardSets->count() > 0)
    <div class="grid grid-cols-1 sm:grid-cols-2 md:grid-cols-3 lg:grid-cols-4 gap-6">
        @foreach($flashcardSets as $set)
        <div class="relative bg-white dark:bg-gray-800 p-6 rounded-lg shadow-sm border dark:border-gray-700 hover:border-blue-600 dark:hover:border-blue-500 transition-all duration-300">
            
            {{-- Form dan Tombol Hapus --}}
            <form action="{{ route('flashcards.destroy', $set) }}" method="POST" class="delete-form absolute top-3 right-3 z-10">
                @csrf
                @method('DELETE')
                <button type="submit" class="p-1 rounded-full text-gray-400 hover:bg-red-100 dark:hover:bg-red-900/50 hover:text-red-500 dark:hover:text-red-400 transition-colors">
                    <span class="material-icons text-base">delete</span>
                </button>
            </form>

            {{-- Konten kartu yang bisa diklik --}}
            <a href="{{ route('flashcards.show', $set) }}" class="flex flex-col h-full">
                <h4 class="font-bold text-lg text-gray-800 dark:text-white">
                    {{ $set->title }}
                </h4>
                <p class="text-sm text-gray-500 dark:text-gray-400 mt-2 flex-grow">
                    {{ Str::limit($set->description, 60) }}
                </p>
                <div class="mt-4 text-sm font-medium text-gray-500 dark:text-gray-400 flex items-center">
                    <span class="material-icons mr-2 text-base">style</span>
                    {{ $set->flashcards_count }} Cards
                </div>
            </a>
        </div>
        @endforeach
    </div>
@else
    <div class="text-center bg-white dark:bg-gray-800 p-12 rounded-lg border dark:border-gray-700">
        <span class="material-icons text-6xl text-gray-400 dark:text-gray-500">search_off</span>
        <h3 class="mt-4 text-xl font-bold text-gray-800 dark:text-white">No Flashcard Sets Found</h3>
        <p class="mt-2 text-gray-500 dark:text-gray-400">Get started by creating a new set.</p>
    </div>
@endif


</div>
@endsection

@section('scripts')

<script>
// JavaScript yang sama untuk konfirmasi hapus
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