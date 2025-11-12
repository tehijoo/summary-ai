@extends('layouts.app')

@section('content')
{{-- Hapus div pembungkus 'max-w-7xl' agar padding dari layout utama berlaku --}}
<div>
    <div class="flex flex-wrap justify-between items-center mb-8 gap-4">
        {{-- Tombol Kembali --}}
        <a href="{{ route('qna.index') }}" class="flex items-center text-text-light-secondary dark:text-dark-secondary hover:text-primary dark:hover:text-primary transition-colors">
            <span class="material-icons-outlined mr-2">arrow_back</span>
            <span>Back to Document List</span>
        </a>
    </div>
    
    {{-- Layout 2 Kolom --}}
    <div class="grid grid-cols-1 lg:grid-cols-2 gap-8">
        
        <div class="bg-surface-light dark:bg-surface-dark p-6 rounded-xl border border-border-light dark:border-border-dark h-[80vh] flex flex-col">
            <h3 class="text-2xl font-bold text-text-light-primary dark:text-dark-primary mb-1">Document Content 📄</h3>
            <p class="text-sm text-text-light-secondary dark:text-dark-secondary mb-4 break-words">{{ $document->original_filename }}</p>
            <div class="prose dark:prose-invert max-w-none text-text-light-primary dark:text-gray-200 text-sm flex-grow overflow-y-auto p-4 bg-background-light dark:bg-background-dark rounded-lg">
                {{ $document->content }}
            </div>
        </div>

        <div class="bg-surface-light dark:bg-surface-dark rounded-xl border border-border-light dark:border-border-dark flex flex-col h-[80vh]">
            <div class="p-6 border-b border-border-light dark:border-border-dark">
                <h3 class="text-2xl font-bold text-text-light-primary dark:text-dark-primary">Ask a Question 💬</h3>
            </div>
            
            <div id="chat-log" class="flex-grow p-6 space-y-4 overflow-y-auto">
                <div class="flex">
                    <div class="bg-primary text-white p-3 rounded-lg max-w-md">
                        <p>Hello! Ask me anything about this document.</p>
                    </div>
                </div>
            </div>

            <div class="p-6 border-t border-border-light dark:border-border-dark">
                <form id="qna-form" class="flex items-center space-x-3">
                    <input type="text" name="question" placeholder="Type your question here..." class="block w-full bg-background-light dark:bg-background-dark border-border-light dark:border-border-dark rounded-lg py-3 px-4 focus:outline-none focus:ring-2 focus:ring-primary text-text-light-primary dark:text-dark-primary" required autocomplete="off">
                    <button type="submit" class="bg-primary text-white p-3 rounded-lg hover:bg-blue-600 transition-colors flex-shrink-0">
                        <span class="material-icons-outlined">send</span>
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