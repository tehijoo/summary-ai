<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Conversation extends Model
{
    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'document_id', // <--- PASTIKAN BARIS INI ADA
        'mode',
        'input',
        'response'
    ];

    /**
     * Sebuah Conversation (ringkasan) dimiliki oleh satu Document.
     */
    public function document(): BelongsTo
    {
        return $this->belongsTo(Document::class);
    }
}