<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\LLMController;
use App\Http\Controllers\FlashcardController;
use App\Models\Document;

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

Route::get('/', [LLMController::class, 'view']);

Route::get('/chat', [LLMController::class, 'view']);
Route::post('/ask', [LLMController::class, 'ask']);
Route::get('/recent-projects', [LLMController::class, 'history'])->name('projects.history');
Route::get('/projects/{conversation}', [App\Http\Controllers\LLMController::class, 'show'])->name('projects.show');
Route::delete('/projects/{conversation}', [LLMController::class, 'destroyProject'])->name('projects.destroy');

Route::get('/flashcards', [FlashcardController::class, 'index'])->name('flashcards.index');
Route::get('/flashcards/create', [FlashcardController::class, 'create'])->name('flashcards.create');
Route::post('/flashcards', [FlashcardController::class, 'store'])->name('flashcards.store');
Route::get('/flashcards/{flashcardSet}', [FlashcardController::class, 'show'])->name('flashcards.show');

Route::get('/qna', [LLMController::class, 'qnaIndex'])->name('qna.index');
Route::post('/qna/upload', [LLMController::class, 'qnaUpload'])->name('qna.upload');
Route::get('/qna/chat/{document}', [LLMController::class, 'qnaChat'])->name('qna.chat');
Route::post('/qna/chat/{document}', [LLMController::class, 'qnaAsk'])->name('qna.ask');

Route::post('/documents/{document}/generate-flashcards', [App\Http\Controllers\LLMController::class, 'generateFlashcards'])->name('documents.generate-flashcards');
Route::post('/flashcards/ai-save', [App\Http\Controllers\LLMController::class, 'saveAiFlashcards'])->name('flashcards.ai-save');