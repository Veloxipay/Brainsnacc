<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

// Which past VCE exams a student has finished (and their score, if they enter one).
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('past_paper_progress', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->string('paper'); // e.g. "chemistry-2023" or "biology-2024-nht"
            $table->unsignedTinyInteger('score')->nullable(); // percentage
            $table->timestamps();
            $table->unique(['user_id', 'paper']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('past_paper_progress');
    }
};
