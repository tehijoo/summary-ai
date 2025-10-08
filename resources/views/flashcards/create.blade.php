@extends('layouts.app')

@section('content')
<div class="max-w-4xl mx-auto">
    <form action="{{ route('flashcards.store') }}" method="POST">
        @csrf
        <div class="flex justify-between items-center mb-12">
            <div>
                <h2 class="text-3xl font-bold text-gray-800 dark:text-white">Create a New Flashcard Set</h2>
                <p class="text-gray-500 dark:text-gray-300 mt-2">Fill in the details for your new set.</p>
            </div>
        </div>

        <div class="bg-white dark:bg-gray-800 p-6 rounded-lg shadow-sm border dark:border-gray-700">
            <div class="mb-4">
                <label for="title" class="block text-sm font-medium text-gray-500 dark:text-gray-300">TITLE</label>
                <input type="text" name="title" id="title" class="mt-1 block w-full bg-gray-50 dark:bg-gray-700 text-gray-800 dark:text-gray-200 border-0 border-b-2 dark:border-gray-600 focus:ring-0 focus:border-blue-600" placeholder="Enter a title..." required>
            </div>
            <div>
                <label for="description" class="block text-sm font-medium text-gray-500 dark:text-gray-300">DESCRIPTION</label>
                <textarea name="description" id="description" rows="2" class="mt-1 block w-full bg-gray-50 dark:bg-gray-700 text-gray-800 dark:text-gray-200 border-0 border-b-2 dark:border-gray-600 focus:ring-0 focus:border-blue-600" placeholder="Add a description..."></textarea>
            </div>
        </div>

        <div id="flashcard-container" class="mt-8 space-y-6">
            <div class="flashcard-item bg-white dark:bg-gray-800 p-6 rounded-lg shadow-sm border dark:border-gray-700">
                <div class="flex justify-between items-center mb-4">
                    <span class="font-bold text-lg text-gray-800 dark:text-white">1</span>
                    <button type="button" class="delete-card-btn text-gray-400 dark:text-gray-500 hover:text-red-500 dark:hover:text-red-400">
                        <span class="material-icons">delete</span>
                    </button>
                </div>
                <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                    <div>
                        <label class="block text-sm font-medium text-gray-500 dark:text-gray-300">TERM</label>
                        <input type="text" name="cards[0][term]" class="mt-1 block w-full bg-gray-50 dark:bg-gray-700 text-gray-800 dark:text-gray-200 border-0 border-b-2 dark:border-gray-600 focus:ring-0 focus:border-blue-600" required>
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-500 dark:text-gray-300">DEFINITION</label>
                        <input type="text" name="cards[0][definition]" class="mt-1 block w-full bg-gray-50 dark:bg-gray-700 text-gray-800 dark:text-gray-200 border-0 border-b-2 dark:border-gray-600 focus:ring-0 focus:border-blue-600" required>
                    </div>
                </div>
            </div>
        </div>

        <button type="button" id="add-card-btn" class="w-full mt-6 bg-white dark:bg-gray-800 p-6 rounded-lg shadow-sm border dark:border-gray-700 text-center font-bold text-gray-800 dark:text-white hover:border-blue-600 dark:hover:border-blue-500 transition-all">
            + ADD A CARD
        </button>
        
        <div class="mt-12 text-right">
            <button type="submit" class="bg-blue-600 text-white font-bold py-3 px-10 rounded-lg hover:bg-blue-700 transition-colors text-lg">
                Create Set
            </button>
        </div>
    </form>
</div>

<script>
    document.addEventListener('DOMContentLoaded', function () {
        const container = document.getElementById('flashcard-container');
        const addCardBtn = document.getElementById('add-card-btn');
        let cardIndex = 1;

        // Fungsi untuk memperbarui nomor urut kartu
        function updateCardNumbers() {
            const cards = container.querySelectorAll('.flashcard-item');
            cards.forEach((card, index) => {
                card.querySelector('span.font-bold').textContent = index + 1;
            });
        }

        // Menambah kartu baru
        addCardBtn.addEventListener('click', function () {
            const newCard = container.querySelector('.flashcard-item').cloneNode(true);

            // Mengosongkan input di kartu baru
            newCard.querySelectorAll('input').forEach(input => {
                input.value = '';
                // Mengupdate index di atribut 'name'
                input.name = input.name.replace(/\[\d+\]/, `[${cardIndex}]`);
            });

            container.appendChild(newCard);
            cardIndex++;
            updateCardNumbers();
        });

        // Menghapus kartu
        container.addEventListener('click', function (e) {
            // Cari tombol delete yang ditekan
            const deleteBtn = e.target.closest('.delete-card-btn');
            if (deleteBtn) {
                // Jangan hapus jika hanya tersisa satu kartu
                if (container.querySelectorAll('.flashcard-item').length > 1) {
                    deleteBtn.closest('.flashcard-item').remove();
                    updateCardNumbers();
                }
            }
        });
    });
</script>
@endsection