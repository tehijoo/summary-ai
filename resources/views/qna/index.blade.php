@extends('layouts.app')

@section('content')
<div class="max-w-6xl mx-auto">
    <div class="mb-12">
        <h2 class="text-3xl font-bold text-gray-800 dark:text-white">Q&A with your Documents</h2>
        <p class="text-gray-500 dark:text-gray-300 mt-2">Upload a new document to start a conversation with it.</p>
    </div>

    <div class="mb-12">
        <form action="{{ route('qna.upload') }}" method="POST" enctype="multipart/form-data">
            @csrf
            <div class="bg-white dark:bg-gray-800 p-6 rounded-lg shadow-sm border dark:border-gray-700">
                <h3 class="text-xl font-bold text-gray-800 dark:text-white mb-4">Upload a New Document</h3>

                @if(session('error'))
                    <div class="mb-4 p-4 text-sm text-red-800 rounded-lg bg-red-50 dark:bg-gray-800 dark:text-red-400" role="alert">
                        {{ session('error') }}
                    </div>
                @endif

                <div class="flex items-center space-x-4">
                    <input type="file" name="document" required class="block w-full text-sm text-gray-900 border border-gray-300 rounded-lg cursor-pointer bg-gray-50 dark:text-gray-400 focus:outline-none dark:bg-gray-700 dark:border-gray-600 dark:placeholder-gray-400">
                    <button type="submit" class="bg-blue-600 text-white font-bold py-2 px-6 rounded-lg hover:bg-blue-700 transition-colors">
                        Upload & Start
                    </button>
                </div>
            </div>
        </form>
    </div>

    <div>
        <h3 class="text-2xl font-bold text-gray-800 dark:text-white mb-6">Previously Uploaded Documents</h3>

        @if($documents->count() > 0)
            <div class="grid grid-cols-1 sm:grid-cols-2 md:grid-cols-3 lg:grid-cols-4 gap-6">
                @foreach($documents as $document)
                <a href="{{ route('qna.chat', $document) }}" class="block bg-white dark:bg-gray-800 p-6 rounded-lg shadow-sm border dark:border-gray-700 hover:border-blue-600 dark:hover:border-blue-500 transition-all duration-300">
                    <div class="flex items-start">
                        <span class="material-icons text-blue-600 dark:text-blue-400 mr-4 mt-1">description</span>
                        <div>
                            <h4 class="font-bold text-gray-800 dark:text-white break-words">
                                {{ $document->original_filename }}
                            </h4>
                            <p class="text-sm text-gray-500 dark:text-gray-400 mt-1">
                                {{ $document->created_at->diffForHumans() }}
                            </p>
                        </div>
                    </div>
                </a>
                @endforeach
            </div>
        @else
            <p class="text-gray-500 dark:text-gray-400">No documents have been uploaded yet.</p>
        @endif
    </div>
</div>
@endsection