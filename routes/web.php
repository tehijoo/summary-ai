<?php

use App\Http\Controllers\FlashcardController;
use App\Http\Controllers\LLMController;
use Illuminate\Support\Facades\Route;

// Redirect halaman utama ke halaman chat
Route::get('/', function () {
    return redirect('/chat');
});

// Rute untuk Summarizer
Route::get('/chat', [LLMController::class, 'view'])->name('chat');
Route::post('/ask', [LLMController::class, 'ask'])->name('ask');

// Rute untuk Riwayat (Recent Projects)
Route::get('/recent-projects', [LLMController::class, 'history'])->name('projects.history');
Route::get('/projects/{conversation}', [LLMController::class, 'show'])->name('projects.show');
Route::delete('/projects/{conversation}', [LLMController::class, 'destroyProject'])->name('projects.destroy');

// Rute untuk Flashcards
Route::get('/flashcards', [FlashcardController::class, 'index'])->name('flashcards.index');
Route::get('/flashcards/create', [FlashcardController::class, 'create'])->name('flashcards.create');
Route::post('/flashcards', [FlashcardController::class, 'store'])->name('flashcards.store');
Route::get('/flashcards/{flashcardSet}', [FlashcardController::class, 'show'])->name('flashcards.show');
Route::delete('/flashcards/{flashcardSet}', [FlashcardController::class, 'destroy'])->name('flashcards.destroy');

// Rute untuk generate flashcard dari dokumen
Route::post('/documents/{document}/generate-flashcards', [LLMController::class, 'generateFlashcards'])->name('documents.generate-flashcards');

// Rute untuk Q&A Document
Route::get('/qna', [LLMController::class, 'qnaIndex'])->name('qna.index');
Route::post('/qna/upload', [LLMController::class, 'qnaUpload'])->name('qna.upload');
Route::get('/qna/chat/{document}', [LLMController::class, 'qnaChat'])->name('qna.chat');
Route::post('/qna/chat/{document}', [LLMController::class, 'qnaAsk'])->name('qna.ask');
Route::delete('/qna/documents/{document}', [LLMController::class, 'destroyDocument'])->name('qna.destroy');