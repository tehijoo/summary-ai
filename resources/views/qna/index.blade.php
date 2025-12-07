@extends('layouts.app')

@section('content')
{{-- Hapus padding wrapper, biarkan layout utama (app.blade.php) yang mengatur --}}
<div>
    {{-- Judul Utama - Dibuat Center --}}
    <div class="text-center mb-12">
        <h2 class="text-4xl font-bold text-gray-900 dark:text-white mb-3">Q&A with your Documents 🧠</h2>
        <p class="text-lg text-gray-500 dark:text-gray-400">Upload a new document to start a conversation with it.</p>
    </div>

    {{-- Form Upload - Didesain ulang agar lebih menarik --}}
    <div class="mb-12">
        <form action="{{ route('qna.upload') }}" method="POST" enctype="multipart/form-data">
            @csrf
            {{-- Menggunakan style kartu yang sama dengan halaman 'Upload Document' --}}
            <div class="bg-surface-light dark:bg-surface-dark p-8 rounded-xl border-2 border-dashed border-gray-300 dark:border-gray-700 text-center flex flex-col items-center justify-center hover:border-primary dark:hover:border-primary transition-all duration-300 dark:animate-pulse-glow">
                
                <span class="material-icons-outlined text-5xl text-primary bg-blue-100 dark:bg-primary/20 p-4 rounded-full">upload_file</span>
                <h3 class="text-xl font-semibold mt-4 mb-2 text-text-light-primary dark:text-dark-primary">Upload a New Document</h3>

                @if(session('error'))
                    <div class="my-4 p-3 text-sm text-red-800 rounded-lg bg-red-50 dark:bg-surface-dark dark:text-red-400 border border-red-200 dark:border-red-600" role="alert">
                        {{ session('error') }}
                    </div>
                @endif

                {{-- Tombol upload dan submit yang sudah di-style --}}
                <div class="flex items-center justify-center mt-4 space-x-4">
                    <label for="document-upload" class="bg-primary text-white font-bold py-3 px-8 rounded-lg hover:bg-blue-600 transition-colors duration-300 cursor-pointer">
                        Choose File
                    </label>
                    <input id="document-upload" type="file" name="document" required class="hidden">
                    <button type="submit" class="bg-surface-light dark:bg-surface-dark border border-border-light dark:border-border-dark text-text-light-primary dark:text-dark-primary font-bold py-3 px-8 rounded-lg hover:border-primary dark:hover:border-primary transition-colors duration-300">
                        Upload & Start
                    </button>
                </div>
                {{-- Tempat untuk menampilkan nama file yang dipilih --}}
                <p id="file-name-display" class="text-sm text-text-light-secondary dark:text-dark-secondary mt-4 h-5"></p>
            </div>
        </form>
    </div>

    {{-- Judul "Previously Uploaded" - Dibuat Center --}}
    <div class="text-center mb-6">
        <h3 class="text-3xl font-bold text-text-light-primary dark:text-dark-primary">Previously Uploaded Documents</h3>
    </div>

    {{-- Notifikasi Sukses Hapus --}}
    @if(session('success'))
        <div class="mb-6 p-4 text-sm text-green-800 rounded-lg bg-green-50 dark:bg-surface-dark dark:text-green-400 border border-green-200 dark:border-green-600" role="alert">
            {{ session('success') }}
        </div>
    @endif

    {{-- Daftar Dokumen (Grid) --}}
    @if($documents->count() > 0)
        <div class="grid grid-cols-1 sm:grid-cols-2 md:grid-cols-3 lg:grid-cols-4 gap-6">
            @foreach($documents as $document)
            {{-- Kartu - Menggunakan warna tema kustom --}}
            <div class="relative bg-surface-light dark:bg-surface-dark p-6 rounded-xl shadow-md dark:shadow-lg dark:shadow-primary/10 border border-border-light dark:border-border-dark hover:border-primary dark:hover:border-primary transition-all duration-300">
                
                <form action="{{ route('qna.destroy', $document) }}" method="POST" class="delete-form absolute top-3 right-3 z-10">
                    @csrf
                    @method('DELETE')
                    <button type="submit" class="p-1 rounded-full text-text-light-secondary/50 dark:text-dark-secondary/50 hover:bg-red-100 dark:hover:bg-red-900/50 hover:text-red-600 dark:hover:text-red-400 transition-colors">
                        <span class="material-icons-outlined text-base">delete</span>
                    </button>
                </form>

                <a href="{{ route('qna.chat', $document) }}" class="flex items-start">
                    <span class="material-icons-outlined text-primary mr-4 mt-1">description</span>
                    <div>
                        <h4 class="font-bold text-text-light-primary dark:text-dark-primary break-words">
                            {{ $document->original_filename }}
                        </h4>
                        <p class="text-sm text-text-light-secondary dark:text-dark-secondary mt-1">
                            {{ $document->created_at->diffForHumans() }}
                        </p>
                    </div>
                </a>
            </div>
            @endforeach
        </div>
    @else
        {{-- Pesan jika kosong --}}
        <div class="text-center bg-surface-light dark:bg-surface-dark p-12 rounded-xl border border-border-light dark:border-border-dark">
            <span class="material-icons-outlined text-6xl text-text-light-secondary dark:text-dark-secondary">search_off</span>
            <h3 class="mt-4 text-xl font-bold text-text-light-primary dark:text-dark-primary">No Documents Found 📂</h3>
            <p class="mt-2 text-text-light-secondary dark:text-dark-secondary">Upload a document to get started.</p>
        </div>
    @endif
</div>
@endsection

@section('scripts')
<script>
    document.addEventListener('DOMContentLoaded', function () {
        // Skrip untuk konfirmasi hapus
        document.querySelectorAll('.delete-form').forEach(form => {
            form.addEventListener('submit', function (event) {
                if (!confirm('Are you sure you want to delete this document?')) {
                    event.preventDefault();
                }
            });
        });

        // Skrip untuk menampilkan nama file yang dipilih
        const fileInput = document.getElementById('document-upload');
        const fileDisplay = document.getElementById('file-name-display');
        if (fileInput) {
            fileInput.addEventListener('change', function() {
                if (this.files.length > 0) {
                    fileDisplay.textContent = `File selected: ${this.files[0].name}`;
                } else {
                    fileDisplay.textContent = '';
                }
            });
        }
    });

    const CHUNK_SIZE = 5 * 1024 * 1024; // 5MB chunks

    async function uploadFileInChunks(file) {
        const totalChunks = Math.ceil(file.size / CHUNK_SIZE);
        const fileId = 'upload_' + Date.now() + '_' + Math.random().toString(36).substr(2, 9);
        const csrfToken = document.querySelector('meta[name="csrf-token"]').getAttribute('content');

        console.log(`Starting chunked upload: ${file.name} (${totalChunks} chunks)`);

        for (let i = 0; i < totalChunks; i++) {
            const start = i * CHUNK_SIZE;
            const end = Math.min(start + CHUNK_SIZE, file.size);
            const chunk = file.slice(start, end);

            const formData = new FormData();
            formData.append('chunk', chunk);
            formData.append('chunk_index', i);
            formData.append('total_chunks', totalChunks);
            formData.append('file_id', fileId);
            formData.append('filename', file.name);

            try {
                const response = await fetch('/qna/upload-chunk', {
                    method: 'POST',
                    headers: {
                        'X-CSRF-TOKEN': csrfToken,
                    },
                    body: formData,
                });

                const data = await response.json();

                if (!data.success) {
                    throw new Error(data.error || 'Chunk upload failed');
                }

                const progress = ((i + 1) / totalChunks) * 100;
                console.log(`Uploaded chunk ${i + 1}/${totalChunks} (${progress.toFixed(1)}%)`);

                // If this is the last chunk and processing is complete
                if (i === totalChunks - 1 && data.document_id) {
                    console.log('Upload complete, redirecting...');
                    window.location.href = `/qna/chat/${data.document_id}`;
                }
            } catch (error) {
                console.error(`Error uploading chunk ${i}:`, error);
                alert(`Upload failed at chunk ${i + 1}: ${error.message}`);
                throw error;
            }
        }
    }

    // Hook into your existing file input
    document.addEventListener('DOMContentLoaded', function() {
        const fileInput = document.querySelector('input[type="file"][name="document"]');
        
        if (fileInput) {
            fileInput.addEventListener('change', async function(e) {
                const file = this.files[0];
                if (file && file.type === 'application/pdf') {
                    // Use chunked upload for files larger than 10MB
                    if (file.size > 10 * 1024 * 1024) {
                        e.preventDefault();
                        await uploadFileInChunks(file);
                        return;
                    }
                }
            });
        }
    });
</script>
@endsection