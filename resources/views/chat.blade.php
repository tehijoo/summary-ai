@extends('layouts.app')

@section('content')
<div class="max-w-4xl mx-auto">
    <div class="mb-12">
        {{-- Perubahan: Tambah warna teks untuk light/dark mode --}}
        <h2 class="text-3xl font-bold text-gray-800 dark:text-white">Upload Your Document</h2>
        <p class="text-gray-500 dark:text-gray-300 mt-2">Upload your documents to create AI-powered summaries. Supported formats: PDF only</p>
    </div>

    <form method="POST" action="{{ url('/ask') }}" enctype="multipart/form-data">
        @csrf

        <div class="grid grid-cols-1 md:grid-cols-2 gap-8 mb-8">

            {{-- KARTU 1: INPUT TEKS --}}
            {{-- Perubahan: Tambah warna latar, border, dan teks untuk dark mode --}}
            <div class="bg-white dark:bg-gray-800 p-6 rounded-lg shadow-sm border dark:border-gray-700 flex flex-col">
                <label for="text" class="block text-lg font-medium text-gray-800 dark:text-white mb-4">Paste Your Text</label>
                <textarea name="text" class="w-full flex-grow p-3 bg-gray-50 dark:bg-gray-700 text-gray-800 dark:text-gray-200 border dark:border-gray-600 rounded-lg" rows="10" placeholder="Enter your content here...">{{ old('text') }}</textarea>
            </div>

            {{-- KARTU 2: UPLOAD FILE --}}
            <div id="drop-zone" class="bg-white dark:bg-gray-800 p-6 rounded-lg shadow-sm border-2 border-dashed dark:border-gray-700 flex flex-col items-center justify-center text-center transition-colors duration-300">
                <div class="bg-blue-100 dark:bg-gray-700 p-4 rounded-full mb-4">
                    <span class="material-icons text-blue-600 dark:text-blue-300" style="font-size: 36px;">cloud_upload</span>
                </div>
                <p class="text-xl font-medium text-gray-800 dark:text-white">Upload a File</p>
                <p class="text-gray-500 dark:text-gray-400 my-2">or drag and drop</p>
                <label for="pdf" class="mt-4 cursor-pointer bg-blue-600 text-white px-6 py-2 rounded-lg font-medium hover:bg-blue-700 transition-colors inline-block">Choose PDF File</label>
                <input type="file" name="pdf" id="pdf" class="hidden" accept=".pdf,.doc,.docx,.txt">

                {{-- Tambahkan elemen ini untuk menampilkan nama file --}}
                <p id="file-info" class="text-sm text-gray-500 dark:text-gray-400 mt-4 h-5"></p> 

                <p class="text-xs text-gray-400 dark:text-gray-500 mt-2">Max file size: 10MB</p>
            </div>

        </div>

        <div class="text-center">
            <button type="submit" class="bg-blue-600 text-white font-bold py-3 px-10 rounded-lg hover:bg-blue-700 transition-colors text-lg">
                Create Summary
            </button>
        </div>
    </form>

    @if(session('response'))
        <div class="mt-12">
            <h3 class="text-2xl font-bold text-gray-800 dark:text-white">Most Recent Summary</h3>
            {{-- Perubahan: Tambah warna latar, border, dan teks untuk dark mode --}}
            <div class="mt-6 bg-green-50 dark:bg-gray-800 border border-green-200 dark:border-gray-700 text-green-800 dark:text-gray-200 p-6 rounded-lg" style="white-space: pre-wrap;">
                {{ session('response') }}
            </div>
        </div>
    @endif
</div>
@endsection

@section('scripts') {{-- Anda bisa menggunakan section terpisah jika mau --}}
<script>
    document.addEventListener('DOMContentLoaded', function () {
        const dropZone = document.getElementById('drop-zone');
        const fileInput = document.getElementById('pdf');
        const fileInfo = document.getElementById('file-info');

        // Mencegah browser membuka file secara default saat di-drag
        ['dragenter', 'dragover', 'dragleave', 'drop'].forEach(eventName => {
            dropZone.addEventListener(eventName, preventDefaults, false);
        });

        function preventDefaults(e) {
            e.preventDefault();
            e.stopPropagation();
        }

        // Memberi highlight saat file di-drag di atas area
        ['dragenter', 'dragover'].forEach(eventName => {
            dropZone.addEventListener(eventName, () => {
                dropZone.classList.add('border-blue-600', 'bg-blue-50', 'dark:bg-gray-700');
            }, false);
        });

        // Menghilangkan highlight saat file meninggalkan area
        ['dragleave', 'drop'].forEach(eventName => {
            dropZone.addEventListener(eventName, () => {
                dropZone.classList.remove('border-blue-600', 'bg-blue-50', 'dark:bg-gray-700');
            }, false);
        });

        // Menangani file yang di-drop
        dropZone.addEventListener('drop', handleDrop, false);

        function handleDrop(e) {
            const dt = e.dataTransfer;
            const files = dt.files;

            // Memasukkan file yang di-drop ke dalam input file tersembunyi
            fileInput.files = files;

            // Menampilkan nama file yang dipilih
            if (files.length > 0) {
                fileInfo.textContent = `File selected: ${files[0].name}`;
            }
        }

        // Menampilkan nama file jika dipilih lewat tombol "Choose File"
        fileInput.addEventListener('change', function() {
            if (this.files.length > 0) {
                fileInfo.textContent = `File selected: ${this.files[0].name}`;
            } else {
                fileInfo.textContent = '';
            }
        });
    });
</script>
@endsection