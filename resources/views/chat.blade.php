@extends('layouts.app')

@section('content')
<div>
    <div class="text-center mb-12">
        <h2 class="text-4xl font-bold text-gray-900 dark:text-white mb-3">Upload Your Document</h2>
        <p class="text-lg text-gray-500 dark:text-gray-400">Upload your documents to create AI-powered summaries. Supported formats: PDF only</p>
    </div>

    {{-- Form Laravel yang fungsional --}}
    <form method="POST" action="{{ url('/ask') }}" enctype="multipart/form-data">
        @csrf
        
        <div class="grid grid-cols-1 lg:grid-cols-2 gap-8 items-start">
            {{-- KARTU PASTE YOUR TEXT --}}
            <div class="flex flex-col gap-6">
                <div class="bg-white dark:bg-surface-dark p-8 rounded-xl border border-gray-200 dark:border-gray-800 relative focus-within:border-primary transition-all duration-300 dark:animate-pulse-glow">
                    <label class="block text-lg font-semibold mb-3 text-gray-900 dark:text-white" for="text">Paste Your Text</label>
                    <textarea name="text" id="text" class="w-full bg-transparent border-gray-300 dark:border-gray-700 rounded-lg placeholder-gray-400 dark:placeholder-gray-500 focus:ring-primary focus:border-primary transition-colors" placeholder="Enter your content here..." rows="12">{{ old('text') }}</textarea>
                </div>
                
                {{-- TOMBOL CREATE SUMMARY --}}
                <button type="submit" id="summary-btn" class="w-full bg-primary text-white font-bold py-4 px-6 rounded-lg hover:bg-blue-600 transition-colors duration-300 shadow-lg shadow-blue-500/20 flex items-center justify-center">
                    <span id="btn-loader" class="material-icons-outlined animate-spin mr-2 hidden">hourglass_top</span>
                    <span id="btn-text">Create Summary</span>
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
                <p class="text-xs text-gray-400 dark:text-gray-500 mt-2">Max file size: 10MB</p>
            </div>
        </div>
    </form>

    {{-- HASIL SUMMARY (Tidak berubah, tapi akan mengikuti tema) --}}
    @if(session('response'))
        <div class="mt-12">
            <h3 class_ ="text-3xl font-bold text-gray-900 dark:text-white mb-6">Most Recent Summary</h3>
            <div class="bg-white dark:bg-surface-dark p-8 rounded-xl border border-gray-200 dark:border-gray-800 prose dark:prose-invert max-w-none" style="white-space: pre-wrap;">
                {!! session('response') !!}
            </div>
        </div>
    @endif
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
        const summaryBtn = document.getElementById('summary-btn');
        const btnLoader = document.getElementById('btn-loader');
        const btnText = document.getElementById('btn-text');

        if (summaryForm && summaryBtn) {
            summaryForm.addEventListener('submit', function() {
                summaryBtn.disabled = true;
                btnLoader.classList.remove('hidden');
                btnText.textContent = 'Summarizing...';
            });
        }

    });
</script>
@endsection