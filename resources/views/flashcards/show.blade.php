@extends('layouts.app')

@section('content')
{{-- CSS Khusus untuk Animasi Balik Kartu --}}
<style>
    .flashcard { perspective: 1000px; }
    .card-inner {
        position: relative; width: 100%; height: 100%;
        transition: transform 0.6s; transform-style: preserve-3d;
    }
    .is-flipped { transform: rotateY(180deg); }
    .card-front, .card-back {
        position: absolute; width: 100%; height: 100%;
        -webkit-backface-visibility: hidden; backface-visibility: hidden;
        display: flex; align-items: center; justify-content: center;
        padding: 2rem; border-radius: 0.75rem; /* rounded-xl */
    }
    .card-back { transform: rotateY(180deg); }
</style>

{{-- Hapus div pembungkus (max-w-4xl) agar padding dari app.blade.php berlaku --}}
<div>
    <div class="flex flex-wrap justify-between items-center mb-8 gap-4">
        {{-- Tombol Kembali --}}
        <a href="{{ route('flashcards.index') }}" class="flex items-center text-text-light-secondary dark:text-dark-secondary hover:text-primary dark:hover:text-primary transition-colors">
            <span class="material-icons-outlined mr-2">arrow_back</span>
            <span>Back to All Sets</span>
        </a>
    </div>
    
    <div class="max-w-2xl mx-auto"> {{-- Batasi lebar kontainer kartu agar fokus --}}
        <div class="mb-12 text-center">
            <h2 class="text-3xl font-bold text-text-light-primary dark:text-dark-primary">{{ $flashcardSet->title }} 🃏</h2>
            <p class="text-text-light-secondary dark:text-dark-secondary mt-2">{{ $flashcardSet->description }}</p>
        </div>

        @if($flashcardSet->flashcards->count() > 0)
            <div id="card-viewer" class="relative">
                @foreach($flashcardSet->flashcards as $index => $card)
                <div class="flashcard h-80 w-full cursor-pointer {{ $index > 0 ? 'hidden' : '' }}" data-index="{{ $index }}">
                    <div class="card-inner">
                        <div class="card-front bg-surface-light dark:bg-surface-dark shadow-xl border border-border-light dark:border-border-dark">
                            <h3 class="text-4xl font-bold text-text-light-primary dark:text-dark-primary text-center">{{ $card->term }}</h3>
                        </div>
                        <div class="card-back bg-primary text-white shadow-xl">
                            <p class="text-2xl text-center">{{ $card->definition }}</p>
                        </div>
                    </div>
                </div>
                @endforeach
            </div>

            <div class="flex items-center justify-between mt-8">
                <button id="prev-btn" class="p-3 rounded-full bg-surface-light dark:bg-surface-dark border border-border-light dark:border-border-dark text-text-light-primary dark:text-dark-primary hover:bg-gray-100 dark:hover:bg-gray-800 disabled:opacity-50 disabled:cursor-not-allowed">
                    <span class="material-icons-outlined">chevron_left</span>
                </button>
                <div id="progress-text" class="font-medium text-text-light-secondary dark:text-dark-secondary">
                    1 / {{ $flashcardSet->flashcards->count() }}
                </div>
                <button id="next-btn" class_ ="p-3 rounded-full bg-surface-light dark:bg-surface-dark border border-border-light dark:border-border-dark text-text-light-primary dark:text-dark-primary hover:bg-gray-100 dark:hover:bg-gray-800 disabled:opacity-50 disabled:cursor-not-allowed">
                    <span class="material-icons-outlined">chevron_right</span>
                </button>
            </div>
        @else
            <p class="text-center text-text-light-secondary dark:text-dark-secondary">This flashcard set is empty. 🤔</p>
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