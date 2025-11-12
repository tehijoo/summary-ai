@extends('layouts.app')

@section('content')
<div class="container mx-auto px-6 py-8">
    <div class="text-center mb-12">
        <h2 class="text-4xl font-bold text-gray-900 dark:text-white mb-3">Recent Projects 🗂️</h2>
        <p class="text-lg text-gray-500 dark:text-gray-400">Here is a list of your past summaries.</p>
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
            {{-- Kontainer kartu utama --}}
            <div class="relative flex flex-col bg-surface-light dark:bg-surface-dark p-6 rounded-xl shadow-md dark:shadow-lg dark:shadow-primary/10 border border-border-light dark:border-border-dark hover:border-primary dark:hover:border-primary transition-all duration-300">
                
                <form action="{{ route('projects.destroy', $c) }}" method="POST" class="delete-form absolute top-3 right-3 z-10">
                    @csrf
                    @method('DELETE')
                    <button type="submit" class="p-1 rounded-full text-text-light-secondary/50 dark:text-dark-secondary/50 hover:bg-red-100 dark:hover:bg-red-900/50 hover:text-red-600 dark:hover:text-red-400 transition-colors">
                        <span class="material-icons-outlined text-base">delete</span>
                    </button>
                </form>

                {{-- Konten kartu yang bisa diklik --}}
                <a href="{{ route('projects.show', $c->id) }}" class="flex flex-col flex-grow h-full">
                    <div class="flex items-start mb-4">
                        <span class="material-icons-outlined text-primary mr-4 mt-1">description</span>
                        <div>
                            <h4 class="font-bold text-text-light-primary dark:text-dark-primary">
                                {{ Str::words($c->input, 5, '...') }}
                            </h4>
                            <p class="text-sm text-text-light-secondary dark:text-dark-secondary mt-1">
                                {{ $c->created_at->diffForHumans() }}
                            </p>
                        </div>
                    </div>
                    
                    <p class="text-sm text-text-light-secondary dark:text-dark-secondary line-clamp-4 flex-grow">
                        {{ $c->response }}
                    </p>
                </a>
            </div>
            @endforeach

        </div>
    @else
        <div class="text-center bg-surface-light dark:bg-surface-dark p-12 rounded-xl border border-border-light dark:border-border-dark">
            <span class="material-icons-outlined text-6xl text-text-light-secondary dark:text-dark-secondary">search_off</span>
            <h3 class="mt-4 text-xl font-bold text-text-light-primary dark:text-dark-primary">No Projects Found 🤷</h3>
            <p class="mt-2 text-text-light-secondary dark:text-dark-secondary">Your recent summaries will appear here.</p>
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
                if (!confirm('Are you sure you want to delete this project? This action cannot be undone.')) {
                    event.preventDefault();
                }
            });
        });
    });
</script>
@endsection