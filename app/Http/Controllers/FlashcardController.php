<?php

namespace App\Http\Controllers;

use App\Models\Flashcard;
use App\Models\FlashcardSet;
use Illuminate\Http\Request;

class FlashcardController extends Controller
{
    /**
     * Menampilkan daftar semua set flashcard.
     */
    public function index()
    {
        // Menggunakan withCount('flashcards') lebih efisien daripada memuat semua relasi
        $flashcardSets = FlashcardSet::withCount('flashcards')->latest()->get();
        return view('flashcards.index', compact('flashcardSets'));
    }

    /**
     * Menampilkan formulir untuk membuat set flashcard baru.
     */
    public function create()
    {
        return view('flashcards.create');
    }

    /**
     * Menyimpan set flashcard baru beserta kartu-kartunya ke database.
     */
    public function store(Request $request)
    {
        // Validasi input utama (judul set)
        $request->validate([
            'title' => 'required|string|max:255',
            'cards' => 'required|array|min:1',
            'cards.*.term' => 'required|string',
            'cards.*.definition' => 'required|string',
        ]);

        // 1. Buat Set Flashcard terlebih dahulu
        $flashcardSet = FlashcardSet::create([
            'title' => $request->title,
            'description' => $request->description,
        ]);

        // 2. Loop melalui setiap kartu yang dikirim dari form dan simpan
        foreach ($request->cards as $cardData) {
            $flashcardSet->flashcards()->create([
                'term' => $cardData['term'],
                'definition' => $cardData['definition'],
            ]);
        }

        // Arahkan pengguna ke halaman untuk melihat set yang baru dibuat
        return redirect()->route('flashcards.show', $flashcardSet);
    }

    /**
     * Menampilkan satu set flashcard spesifik.
     */
    public function show(FlashcardSet $flashcardSet)
    {
        // Laravel akan otomatis memuat set flashcard beserta kartu-kartunya
        $flashcardSet->load('flashcards');
        return view('flashcards.show', compact('flashcardSet'));
    }
}