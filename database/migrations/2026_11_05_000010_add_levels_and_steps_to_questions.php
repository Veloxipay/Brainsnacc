<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/*
 * Question levels (easy, medium, hard, challenge), the worked steps behind each answer,
 * and a note for each wrong option saying which step usually goes wrong.
 * Plus a record of the steps a student typed after a wrong answer, and the feedback they got.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('questions', function (Blueprint $table) {
            $table->string('difficulty')->nullable()->after('type'); // easy, medium, hard, challenge
            $table->json('steps')->nullable()->after('explanation');  // ["Step text — reason", …]
            $table->json('mistakes')->nullable()->after('steps');     // {"A": "Step 2: …", …} for wrong options
        });

        Schema::create('step_checks', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->foreignId('question_id')->constrained()->cascadeOnDelete();
            $table->string('answer');
            $table->text('student_steps');
            $table->text('feedback');
            $table->string('source'); // ai or guide
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('step_checks');
        Schema::table('questions', function (Blueprint $table) {
            $table->dropColumn(['difficulty', 'steps', 'mistakes']);
        });
    }
};
