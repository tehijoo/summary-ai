<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('conversations', function (Blueprint $table) {
            // Menambahkan kolom baru untuk menghubungkan ke tabel 'documents'
            // 'nullable' berarti data lama yang tidak punya link tidak akan error
            // 'after' menempatkan kolom ini setelah kolom 'id' agar rapi
            $table->foreignId('document_id')->nullable()->constrained()->onDelete('cascade')->after('id');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('conversations', function (Blueprint $table) {
            // Menghapus foreign key constraint terlebih dahulu
            $table->dropForeign(['document_id']);
            // Menghapus kolomnya
            $table->dropColumn('document_id');
        });
    }
};