<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Tambah user_id ke tabel documents
        Schema::table('documents', function (Blueprint $table) {
            $table->foreignId('user_id')->nullable()->after('id')->constrained()->onDelete('cascade');
        });

        // Tambah user_id ke tabel conversations
        Schema::table('conversations', function (Blueprint $table) {
            $table->foreignId('user_id')->nullable()->after('id')->constrained()->onDelete('cascade');
        });

        // Tambah user_id ke tabel flashcard_sets
        Schema::table('flashcard_sets', function (Blueprint $table) {
            $table->foreignId('user_id')->nullable()->after('id')->constrained()->onDelete('cascade');
        });
    }

    public function down(): void
    {
        Schema::table('documents', function (Blueprint $table) { $table->dropForeign(['user_id']); $table->dropColumn('user_id'); });
        Schema::table('conversations', function (Blueprint $table) { $table->dropForeign(['user_id']); $table->dropColumn('user_id'); });
        Schema::table('flashcard_sets', function (Blueprint $table) { $table->dropForeign(['user_id']); $table->dropColumn('user_id'); });
    }
};