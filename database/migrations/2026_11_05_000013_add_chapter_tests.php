<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/*
 * Chapter tests: a lesson can be a "test" (15 questions, no hints, marked at the end).
 * Each test question names the lesson topic it tests, so results can say what to revise.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('lessons', function (Blueprint $table) {
            $table->string('kind')->default('lesson')->after('title'); // lesson, test
        });
        Schema::table('questions', function (Blueprint $table) {
            $table->string('topic')->nullable()->after('difficulty'); // lesson title this question tests
        });
        Schema::create('test_attempts', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->foreignId('lesson_id')->constrained()->cascadeOnDelete();
            $table->unsignedInteger('score');
            $table->unsignedInteger('total');
            $table->json('answers');   // {question_id: "B"}
            $table->json('by_topic');  // {"Ratios": [2, 3]}
            $table->unsignedInteger('seconds')->nullable();
            $table->timestamps();
            $table->index(['user_id', 'lesson_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('test_attempts');
        Schema::table('questions', fn (Blueprint $t) => $t->dropColumn('topic'));
        Schema::table('lessons', fn (Blueprint $t) => $t->dropColumn('kind'));
    }
};
