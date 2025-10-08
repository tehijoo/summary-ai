<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up()
{
    Schema::create('conversations', function (Blueprint $table) {
        $table->id();
        $table->string('mode')->default('summarize'); // Task type
        $table->longText('input');   // Original input text or PDF content
        $table->longText('response'); // Summarized result
        $table->timestamps();
    });
}

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('conversations');
    }
};
