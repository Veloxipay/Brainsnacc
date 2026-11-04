<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

// A student's sitting of a Brainsnacc practice exam: their answers, the auto-marked
// multiple-choice score and their self-marked written score.
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('mock_exam_attempts', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->string('exam');
            $table->json('answers_a')->nullable();
            $table->json('answers_b')->nullable();
            $table->json('marks_b')->nullable();
            $table->unsignedSmallInteger('score_a')->nullable();
            $table->unsignedSmallInteger('score_b')->nullable();
            $table->timestamp('submitted_at')->nullable();
            $table->timestamp('marked_at')->nullable();
            $table->timestamps();
            $table->index(['user_id', 'exam']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('mock_exam_attempts');
    }
};
