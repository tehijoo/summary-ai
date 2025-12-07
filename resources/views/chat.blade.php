@extends('layouts.app')

@section('content')
<div>
    <div class="text-center mb-12">
        <h2 class="text-4xl font-bold text-gray-900 dark:text-white mb-3">Upload Your Document</h2>
        <p class="text-lg text-gray-500 dark:text-gray-400">Upload your documents to create AI-powered summaries. Supported formats: PDF only</p>
    </div>

    {{-- Form Laravel yang fungsional --}}
    <form id="chatForm" action="{{ route('ask') }}" method="POST" enctype="multipart/form-data">
        @csrf
        
        <div class="grid grid-cols-1 lg:grid-cols-2 gap-8 items-start">
            {{-- KARTU PASTE YOUR TEXT --}}
            <div class="flex flex-col gap-6">
                <div class="bg-white dark:bg-surface-dark p-8 rounded-xl border border-gray-200 dark:border-gray-800 relative focus-within:border-primary transition-all duration-300 dark:animate-pulse-glow">
                    <label class="block text-lg font-semibold mb-3 text-gray-900 dark:text-white" for="text">Paste Your Text</label>
                    <textarea name="text" id="textInput" class="form-control w-full bg-transparent border-gray-300 dark:border-gray-700 rounded-lg placeholder-gray-400 dark:placeholder-gray-500 focus:ring-primary focus:border-primary transition-colors" placeholder="Enter your content here..." rows="12">{{ old('text') }}</textarea>
                </div>
                
                {{-- TOMBOL CREATE SUMMARY --}}
                <button type="submit" id="submitBtn" class="w-full bg-primary text-white font-bold py-4 px-6 rounded-lg hover:bg-blue-600 transition-colors duration-300 shadow-lg shadow-blue-500/20 flex items-center justify-center">
                    <span id="submitText">Create Summary</span>
                    <span id="loadingSpinner" class="spinner-border spinner-border-sm ms-2" style="display: none;">
                    </span>
                </button>
            </div>

            {{-- KARTU UPLOAD A FILE --}}
            <div id="drop-zone" class="bg-white dark:bg-surface-dark p-8 rounded-xl border-2 border-dashed border-gray-300 dark:border-gray-700 text-center flex flex-col items-center justify-center h-full hover:border-primary dark:hover:border-primary transition-all duration-300 dark:animate-pulse-glow">
                <div class="mb-4">
                    <span class="material-icons-outlined text-5xl text-primary bg-blue-100 dark:bg-primary/20 p-4 rounded-full">cloud_upload</span>
                </div>
                <h3 class="text-xl font-semibold mb-1 text-gray-900 dark:text-white">Upload a File</h3>
                <p class="text-gray-500 dark:text-gray-400 mb-6">or drag and drop</p>
                <label for="pdf" class="bg-primary text-white font-bold py-3 px-8 rounded-lg hover:bg-blue-600 transition-colors duration-300 cursor-pointer">
                    Choose PDF File
                </label>
                {{-- Input file tersembunyi yang fungsional --}}
                <input type="file" name="pdf" id="pdf" class="hidden" accept=".pdf">
                <p id="file-info" class="text-sm text-gray-500 dark:text-gray-400 mt-4 h-5"></p> 
                <p class="text-xs text-gray-500 dark:text-gray-400 mt-2">
                    Max file size: {{ $maxFileSize }}MB (PDF only)
                </p>
            </div>
        </div>
    </form>

    {{-- HASIL SUMMARY (Tidak berubah, tapi akan mengikuti tema) --}}
    @if(session('response'))
        <div class="mt-12">
            <h3 class_ ="text-3xl font-bold text-gray-900 dark:text-white mb-6">Most Recent Summary</h3>
            <div class="bg-white dark:bg-surface-dark p-8 rounded-xl border border-gray-200 dark:border-gray-800 prose dark:prose-invert max-w-none">
                {!! session('response') !!}
            </div>
        </div>
    @endif

    {{-- Error Handling --}}
    @if ($errors->any())
        <div class="alert alert-danger alert-dismissible fade show" role="alert">
            <strong>Error:</strong>
            @foreach ($errors->all() as $error)
                <div>{{ $error }}</div>
            @endforeach
            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
        </div>
    @endif

    @if (session('error'))
        <div class="alert alert-danger alert-dismissible fade show" role="alert">
            <strong>Error:</strong> {{ session('error') }}
            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
        </div>
    @endif
</div>

<!-- Loading Overlay -->
<div id="loadingOverlay" class="loading-overlay" style="display: none;">
    <div class="spinner-container">
        <div class="spinner"></div>
        <p>Thinking...</p>
    </div>
</div>
@endsection

@section('scripts')
{{-- Skrip fungsionalitas Anda tidak perlu diubah, cukup salin dari kode sebelumnya --}}
<script>
    document.addEventListener('DOMContentLoaded', function () {
        
        // --- DRAG AND DROP SCRIPT ---
        const dropZone = document.getElementById('drop-zone');
        const fileInput = document.getElementById('pdf');
        const fileInfo = document.getElementById('file-info');

        if (dropZone) {
            ['dragenter', 'dragover', 'dragleave', 'drop'].forEach(eventName => {
                dropZone.addEventListener(eventName, preventDefaults, false);
            });

            function preventDefaults(e) {
                e.preventDefault();
                e.stopPropagation();
            }

            ['dragenter', 'dragover'].forEach(eventName => {
                dropZone.addEventListener(eventName, () => {
                    dropZone.classList.add('border-primary', 'dark:border-primary', 'bg-blue-50', 'dark:bg-primary/10');
                }, false);
            });

            ['dragleave', 'drop'].forEach(eventName => {
                dropZone.addEventListener(eventName, () => {
                    dropZone.classList.remove('border-primary', 'dark:border-primary', 'bg-blue-50', 'dark:bg-primary/10');
                }, false);
            });

            dropZone.addEventListener('drop', function (e) {
                const dt = e.dataTransfer;
                const files = dt.files;
                fileInput.files = files;
                if (files.length > 0) {
                    fileInfo.textContent = `File selected: ${files[0].name}`;
                }
            }, false);

            fileInput.addEventListener('change', function() {
                if (this.files.length > 0) {
                    fileInfo.textContent = `File selected: ${this.files[0].name}`;
                } else {
                    fileInfo.textContent = '';
                }
            });
        }

        // --- LOADING BUTTON SCRIPT ---
        const summaryForm = document.querySelector('form');
        const summaryBtn = document.getElementById('submitBtn');
        const btnLoader = document.getElementById('loadingSpinner');
        const btnText = document.getElementById('submitText');

        if (summaryForm && summaryBtn) {
            summaryForm.addEventListener('submit', function() {
                summaryBtn.disabled = true;
                btnLoader.classList.remove('hidden');
                btnText.textContent = 'Summarizing...';
            });
        }

    });

    document.getElementById('chatForm').addEventListener('submit', function() {
        // Hide submit text and show spinner
        document.getElementById('submitText').style.display = 'none';
        document.getElementById('loadingSpinner').style.display = 'inline-block';
        
        // Disable the button to prevent multiple submissions
        document.getElementById('submitBtn').disabled = true;
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

<style>
    .loading-overlay {
        position: fixed;
        top: 0;
        left: 0;
        right: 0;
        bottom: 0;
        background: rgba(0, 0, 0, 0.5);
        display: flex;
        align-items: center;
        justify-content: center;
        z-index: 9999;
    }

    .spinner-container {
        text-align: center;
        background: white;
        padding: 40px;
        border-radius: 8px;
        box-shadow: 0 4px 6px rgba(0, 0, 0, 0.1);
    }

    .spinner {
        border: 4px solid #f3f3f3;
        border-top: 4px solid #3498db;
        border-radius: 50%;
        width: 50px;
        height: 50px;
        animation: spin 1s linear infinite;
        margin: 0 auto 20px;
    }

    @keyframes spin {
        0% { transform: rotate(0deg); }
        100% { transform: rotate(360deg); }
    }

    .spinner-container p {
        margin: 0;
        color: #333;
        font-weight: 500;
    }

    .spinner-border {
        display: inline-block;
        width: 1rem;
        height: 1rem;
        vertical-align: text-bottom;
        border: 0.25em solid currentColor;
        border-right-color: transparent;
        border-radius: 50%;
        animation: spinner-border 0.75s linear infinite;
    }

    @keyframes spinner-border {
        to {
            transform: rotate(360deg);
        }
    }
</style>
@endsection