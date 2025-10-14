@extends('layouts.app')

@section('content')
<div class="max-w-6xl mx-auto">
    <div class="mb-12">
        <h2 class="text-3xl font-bold text-gray-800 dark:text-white">Recent Projects</h2>
        <p class="text-gray-500 dark:text-gray-300 mt-2">Here is a list of your past summaries.</p>
    </div>

    {{-- Menampilkan pesan sukses setelah menghapus --}}
    @if(session('success'))
        <div class="mb-6 p-4 text-sm text-green-800 rounded-lg bg-green-50 dark:bg-surface-dark dark:text-green-400 border border-green-200 dark:border-green-600" role="alert">
            {{ session('success') }}
        </div>
    @endif

    @if(isset($conversations) && $conversations->count() > 0)
        <div class="grid grid-cols-1 sm:grid-cols-2 md:grid-cols-3 lg:grid-cols-4 gap-6">
            
            @foreach($conversations as $c)
            {{-- Kontainer kartu utama dengan posisi relative --}}
            <div class="relative flex flex-col bg-surface-light dark:bg-surface-dark rounded-lg shadow-md dark:shadow-lg dark:shadow-primary/10 border border-border-light dark:border-border-dark hover:border-primary dark:hover:border-primary transition-all duration-300">
                
                <form action="{{ route('projects.destroy', $c) }}" method="POST" class="delete-form absolute top-3 right-3 z-10">
                    @csrf
                    @method('DELETE')
                    <button type="submit" class="p-1 rounded-full text-text-light-secondary/50 dark:text-dark-secondary/50 hover:bg-red-100 dark:hover:bg-red-900/50 hover:text-red-600 dark:hover:text-red-400 transition-colors">
                        <span class="material-icons text-base">delete</span>
                    </button>
                </form>

                {{-- Konten kartu yang bisa diklik --}}
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
                    
                    {{-- Pratinjau ringkasan dengan line-clamp dan flex-grow --}}
                    <p class="text-sm text-text-light-secondary dark:text-dark-secondary line-clamp-4 flex-grow">
                        {{ $c->response }}
                    </p>
                </a>
            </div>
            @endforeach

        </div>
    @else
        <p class="text-text-light-secondary dark:text-dark-secondary">No conversation history yet.</p>
    @endif
</div>
@endsection

@section('scripts')
<script>
    // Menambahkan konfirmasi sebelum form hapus dikirim
    document.addEventListener('DOMContentLoaded', function () {
        const deleteForms = document.querySelectorAll('.delete-form');
        deleteForms.forEach(form => {
            form.addEventListener('submit', function (event) {
                const confirmation = confirm('Are you sure you want to delete this project? This action cannot be undone.');
                if (!confirmation) {
                    event.preventDefault();
                }
            });
        });
    });
</script>
@endsection