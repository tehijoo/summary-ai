@extends('layouts.app')

@section('content')
<div class="max-w-7xl mx-auto">
    <div class="mb-8">
        <a href="{{ route('qna.index') }}" class="flex items-center text-gray-500 dark:text-gray-300 hover:text-blue-600 dark:hover:text-white transition-colors">
            <span class="material-icons mr-2">arrow_back</span>
            Back to Document List
        </a>
    </div>

    <div class="grid grid-cols-1 lg:grid-cols-2 gap-8">

        <div class="bg-white dark:bg-gray-800 p-6 rounded-lg shadow-sm border dark:border-gray-700">
            <h3 class="text-xl font-bold text-gray-800 dark:text-white mb-1">Document Content</h3>
            <p class="text-sm text-gray-500 dark:text-gray-400 mb-4">{{ $document->original_filename }}</p>
            <div class="prose dark:prose-invert max-w-none text-sm h-[70vh] overflow-y-auto p-4 bg-gray-50 dark:bg-gray-900 rounded">
                {{ $document->content }}
            </div>
        </div>

        <div class="bg-white dark:bg-gray-800 rounded-lg shadow-sm border dark:border-gray-700 flex flex-col h-[80vh]">
            <div class="p-4 border-b dark:border-gray-700">
                <h3 class="text-xl font-bold text-gray-800 dark:text-white">Ask a Question</h3>
            </div>

            <div id="chat-log" class="flex-grow p-6 space-y-4 overflow-y-auto">
                <div class="flex">
                    <div class="bg-blue-500 text-white p-3 rounded-lg max-w-md">
                        <p>Hello! Ask me anything about this document.</p>
                    </div>
                </div>
            </div>

            <div class="p-4 border-t dark:border-gray-700">
                <form id="qna-form" class="flex items-center space-x-2">
                    <input type="text" name="question" placeholder="Type your question here..." class="block w-full bg-gray-100 dark:bg-gray-700 text-gray-800 dark:text-gray-200 border-gray-300 dark:border-gray-600 rounded-full py-2 px-4 focus:outline-none focus:ring-2 focus:ring-blue-500" required autocomplete="off">
                    <button type="submit" class="bg-blue-600 text-white p-3 rounded-full hover:bg-blue-700 transition-colors flex-shrink-0">
                        <span class="material-icons">send</span>
                    </button>
                </form>
            </div>
        </div>

    </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', function () {
    const form = document.getElementById('qna-form');
    const chatLog = document.getElementById('chat-log');
    const userInput = form.querySelector('input[name="question"]');
    const csrfToken = document.querySelector('meta[name="csrf-token"]').getAttribute('content');

    form.addEventListener('submit', function (e) {
        e.preventDefault(); // Mencegah form reload halaman

        const question = userInput.value;
        if (!question.trim()) return;

        // 1. Tampilkan pertanyaan pengguna di chat log
        appendMessage(question, 'user');
        userInput.value = ''; // Kosongkan input

        // 2. Tampilkan pesan loading
        const loadingMessage = appendMessage('AI is thinking...', 'ai', true);

        // 3. Kirim pertanyaan ke server (AJAX/Fetch)
        fetch("{{ route('qna.ask', $document) }}", {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'X-CSRF-TOKEN': csrfToken
            },
            body: JSON.stringify({ question: question })
        })
        .then(response => response.json())
        .then(data => {
            // 4. Ganti pesan loading dengan jawaban dari AI
            loadingMessage.querySelector('p').textContent = data.answer;
        })
        .catch(error => {
            console.error('Error:', error);
            loadingMessage.querySelector('p').textContent = 'Sorry, an error occurred.';
            loadingMessage.classList.remove('bg-blue-500');
            loadingMessage.classList.add('bg-red-500');
        });
    });

    function appendMessage(text, role, isLoading = false) {
        const messageWrapper = document.createElement('div');
        messageWrapper.className = 'flex';

        const messageBubble = document.createElement('div');
        messageBubble.className = 'p-3 rounded-lg max-w-md';

        if (role === 'user') {
            messageWrapper.classList.add('justify-end');
            messageBubble.classList.add('bg-gray-200', 'dark:bg-gray-600', 'text-gray-800', 'dark:text-gray-200');
        } else { // 'ai'
            messageBubble.classList.add('bg-blue-500', 'text-white');
        }

        const content = document.createElement('p');
        content.textContent = text;
        if (isLoading) {
            content.classList.add('animate-pulse');
        }

        messageBubble.appendChild(content);
        messageWrapper.appendChild(messageBubble);
        chatLog.appendChild(messageWrapper);

        // Scroll ke pesan terbaru
        chatLog.scrollTop = chatLog.scrollHeight;

        return messageBubble; // Kembalikan elemen bubble untuk diupdate nanti
    }
});
</script>
@endsection