@extends('layouts.app')

@section('content')
{{-- CSS Khusus untuk Animasi Balik Kartu --}}
<style>
    .flashcard { perspective: 1000px; }
    .card-inner {
        position: relative;
        width: 100%;
        height: 100%;
        transition: transform 0.6s;
        transform-style: preserve-3d;
    }
    .is-flipped {
        transform: rotateY(180deg);
    }
    .card-front, .card-back {
        position: absolute;
        width: 100%;
        height: 100%;
        -webkit-backface-visibility: hidden; /* Safari */
        backface-visibility: hidden;
        display: flex;
        align-items: center;
        justify-content: center;
        padding: 2rem;
        border-radius: 0.5rem; /* rounded-lg */
    }
    .card-back {
        transform: rotateY(180deg);
    }
</style>

<div class="max-w-4xl mx-auto">
        <div class="mb-8">
            <a href="{{ route('flashcards.index') }}" class="flex items-center text-gray-500 dark:text-gray-300 hover:text-blue-600 dark:hover:text-white transition-colors">
                <span class="material-icons mr-2">arrow_back</span>
                Back to All Sets
            </a>
        </div>
        
        <div class="mb-12 text-center">
            <h2 class="text-3xl font-bold text-gray-800 dark:text-white">{{ $flashcardSet->title }}</h2>
            <p class="text-gray-500 dark:text-gray-300 mt-2">{{ $flashcardSet->description }}</p>
        </div>

        @if($flashcardSet->flashcards->count() > 0)
            <div id="card-viewer" class="relative">
                @foreach($flashcardSet->flashcards as $index => $card)
                <div class="flashcard h-80 w-full cursor-pointer {{ $index > 0 ? 'hidden' : '' }}" data-index="{{ $index }}">
                    <div class="card-inner">
                        <div class="card-front bg-white dark:bg-gray-800 shadow-lg border dark:border-gray-700">
                            <h3 class="text-4xl font-bold text-gray-800 dark:text-white text-center">{{ $card->term }}</h3>
                        </div>
                        <div class="card-back bg-blue-600 text-white shadow-lg">
                            <p class="text-2xl text-center">{{ $card->definition }}</p>
                        </div>
                    </div>
                </div>
                @endforeach
            </div>

            <div class="flex items-center justify-between mt-8">
                <button id="prev-btn" class="p-3 rounded-full bg-white dark:bg-gray-800 border dark:border-gray-700 text-gray-800 dark:text-white hover:bg-gray-100 dark:hover:bg-gray-700 disabled:opacity-50">
                    <span class="material-icons">chevron_left</span>
                </button>
                <div id="progress-text" class="font-medium text-gray-500 dark:text-gray-400">
                    1 / {{ $flashcardSet->flashcards->count() }}
                </div>
                <button id="next-btn" class="p-3 rounded-full bg-white dark:bg-gray-800 border dark:border-gray-700 text-gray-800 dark:text-white hover:bg-gray-100 dark:hover:bg-gray-700 disabled:opacity-50">
                    <span class="material-icons">chevron_right</span>
                </button>
            </div>
        @else
            <p class="text-center text-gray-500 dark:text-gray-400">This flashcard set is empty.</p>
        @endif
    </div>

<script>
    document.addEventListener('DOMContentLoaded', function () {
        const cards = document.querySelectorAll('.flashcard');
        if (cards.length === 0) return;

        const nextBtn = document.getElementById('next-btn');
        const prevBtn = document.getElementById('prev-btn');
        const progressText = document.getElementById('progress-text');
        let currentIndex = 0;

        function updateUI() {
            // Sembunyikan semua kartu
            cards.forEach(card => card.classList.add('hidden'));
            // Tampilkan kartu yang aktif
            cards[currentIndex].classList.remove('hidden');

            // Reset kartu agar kembali ke sisi depan
            cards[currentIndex].querySelector('.card-inner').classList.remove('is-flipped');

            // Update teks progress
            progressText.textContent = `${currentIndex + 1} / ${cards.length}`;

            // Atur status tombol prev/next
            prevBtn.disabled = currentIndex === 0;
            nextBtn.disabled = currentIndex === cards.length - 1;
        }

        // Event listener untuk membalik kartu saat diklik
        cards.forEach(card => {
            card.addEventListener('click', () => {
                card.querySelector('.card-inner').classList.toggle('is-flipped');
            });
        });

        // Event listener untuk tombol Next
        nextBtn.addEventListener('click', () => {
            if (currentIndex < cards.length - 1) {
                currentIndex++;
                updateUI();
            }
        });

        // Event listener untuk tombol Previous
        prevBtn.addEventListener('click', () => {
            if (currentIndex > 0) {
                currentIndex--;
                updateUI();
            }
        });

        // Inisialisasi tampilan awal
        updateUI();
    });
</script>
@endsection