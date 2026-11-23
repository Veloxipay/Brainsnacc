<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/*
 * Tools for a real tutor (or parent) teaching students:
 * - homework: a lesson or chapter test set for one student, with an optional due date and note.
 *   It is handed in when the student finishes that lesson (or sits that test) after it was set.
 * - session_notes: a short note after each teaching session, seen by everyone who looks after the student.
 * - help_requests: "Ask my tutor" — a student flags a question (or a Maths tutor problem) to go through together.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('homework', function (Blueprint $table) {
            $table->id();
            $table->foreignId('student_id')->constrained('users')->cascadeOnDelete();
            $table->foreignId('set_by')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('lesson_id')->constrained()->cascadeOnDelete();
            $table->date('due_on')->nullable();
            $table->text('note')->nullable();
            $table->timestamp('completed_at')->nullable();
            $table->timestamps();
            $table->index(['student_id', 'completed_at']);
        });

        Schema::create('session_notes', function (Blueprint $table) {
            $table->id();
            $table->foreignId('student_id')->constrained('users')->cascadeOnDelete();
            $table->foreignId('author_id')->nullable()->constrained('users')->nullOnDelete();
            $table->date('taught_on');
            $table->text('body');
            $table->timestamps();
            $table->index(['student_id', 'taught_on']);
        });

        Schema::create('help_requests', function (Blueprint $table) {
            $table->id();
            $table->foreignId('student_id')->constrained('users')->cascadeOnDelete();
            $table->foreignId('question_id')->nullable()->constrained()->cascadeOnDelete();
            $table->foreignId('tutor_session_id')->nullable()->constrained()->cascadeOnDelete();
            $table->text('message')->nullable();
            $table->timestamp('resolved_at')->nullable();
            $table->foreignId('resolved_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();
            $table->index(['student_id', 'resolved_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('help_requests');
        Schema::dropIfExists('session_notes');
        Schema::dropIfExists('homework');
    }
};
