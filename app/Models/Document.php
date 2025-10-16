<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasOne;

class Document extends Model
{
    use HasFactory;

    protected $fillable = [
        'original_filename',
        'content',
    ];
    public function conversation(): HasOne // <-- Tambahkan method ini
    {
        return $this->hasOne(Conversation::class);
    }
}