<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\LLMController;
use App\Http\Controllers\FlashcardController;

/*
|--------------------------------------------------------------------------
| Web Routes
|--------------------------------------------------------------------------
|
| Here is where you can register web routes for your application. These
| routes are loaded by the RouteServiceProvider and all of them will
| be assigned to the "web" middleware group. Make something great!
|
*/


// Arahkan halaman utama ke login
Route::get('/', function () {
    return redirect()->route('login');
});

// ========================================================
// ## RUTE PUBLIK (Bisa diakses Tamu / Stay Logout) ##
// ========================================================
Route::get('/chat', [LLMController::class, 'view'])->name('chat');
Route::post('/ask', [LLMController::class, 'ask'])->name('ask');


// ========================================================
// ## RUTE PRIVAT (Wajib Login untuk akses) ##
// ========================================================
Route::middleware(['auth'])->group(function () {

    // Recent Projects (Riwayat)
    Route::get('/recent-projects', [LLMController::class, 'history'])->name('projects.history');
    Route::get('/projects/{conversation}', [LLMController::class, 'show'])->name('projects.show');
    Route::delete('/projects/{conversation}', [LLMController::class, 'destroyProject'])->name('projects.destroy');

    // Flashcards (Riwayat)
    Route::get('/flashcards', [FlashcardController::class, 'index'])->name('flashcards.index');
    Route::get('/flashcards/create', [FlashcardController::class, 'create'])->name('flashcards.create');
    Route::post('/flashcards', [FlashcardController::class, 'store'])->name('flashcards.store');
    Route::get('/flashcards/{flashcardSet}', [FlashcardController::class, 'show'])->name('flashcards.show');
    Route::delete('/flashcards/{flashcardSet}', [FlashcardController::class, 'destroy'])->name('flashcards.destroy');
    Route::post('/documents/{document}/generate-flashcards', [LLMController::class, 'generateFlashcards'])->name('documents.generate-flashcards');

    // Q&A Document (Riwayat)
    Route::get('/qna', [LLMController::class, 'qnaIndex'])->name('qna.index');
    Route::post('/qna/upload', [LLMController::class, 'qnaUpload'])->name('qna.upload');
    Route::get('/qna/chat/{document}', [LLMController::class, 'qnaChat'])->name('qna.chat');
    Route::post('/qna/chat/{document}', [LLMController::class, 'qnaAsk'])->name('qna.ask');
    Route::delete('/qna/documents/{document}', [LLMController::class, 'destroyDocument'])->name('qna.destroy');
});

// Ini memuat rute login, register, logout, dll.
require __DIR__.'/auth.php';