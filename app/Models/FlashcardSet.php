<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class FlashcardSet extends Model
{
    use HasFactory;

    protected $fillable = ['title', 'description'];

    /**
     * Sebuah Set memiliki banyak Flashcard.
     */
    public function flashcards(): HasMany
    {
        return $this->hasMany(Flashcard::class);
    }
}