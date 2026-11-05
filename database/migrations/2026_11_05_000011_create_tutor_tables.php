<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/*
 * The Maths tutor: skills (with prerequisites), tutoring sessions, each line of working the student typed,
 * the help they used, the mistakes found, quick prerequisite checks, the honest per-skill record,
 * and a log of every AI call (for cost tracking and the daily cap).
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('skills', function (Blueprint $table) {
            $table->id();
            $table->string('code')->unique();          // EQ.BRACKETS
            $table->string('name');
            $table->unsignedTinyInteger('year');
            $table->string('strand');                   // Number, Algebra, …
            $table->text('objective')->nullable();
            $table->string('curriculum_code')->nullable(); // VCAA code, once checked
            $table->string('mapping_status')->default('pending');
            $table->timestamps();
        });

        Schema::create('skill_prerequisites', function (Blueprint $table) {
            $table->id();
            $table->string('skill_code');
            $table->string('prerequisite_code');
            $table->unique(['skill_code', 'prerequisite_code']);
        });

        Schema::create('tutor_sessions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->string('skill_code');
            $table->string('source');                  // homework, practice, photo
            $table->string('mode')->default('guided'); // guided, independent
            $table->string('question');                 // as confirmed by the student
            $table->string('solution');                 // exact, e.g. "3" or "-7/2"
            $table->string('status')->default('working'); // working, solved, left
            $table->unsignedInteger('help_used')->default(0);
            $table->unsignedTinyInteger('hint_level')->default(0);
            $table->foreignId('parent_id')->nullable()->constrained('tutor_sessions')->nullOnDelete();
            $table->timestamp('solved_at')->nullable();
            $table->timestamps();
            $table->index(['user_id', 'skill_code']);
        });

        Schema::create('working_lines', function (Blueprint $table) {
            $table->id();
            $table->foreignId('tutor_session_id')->constrained()->cascadeOnDelete();
            $table->unsignedInteger('position');
            $table->string('typed');
            $table->string('read_as')->nullable();      // how the checker read it
            $table->string('status');                    // correct, wrong, unreadable
            $table->boolean('is_final')->default(false);
            $table->string('misconception')->nullable();
            $table->string('confidence')->nullable();
            $table->text('feedback')->nullable();
            $table->timestamps();
        });

        Schema::create('help_events', function (Blueprint $table) {
            $table->id();
            $table->foreignId('tutor_session_id')->constrained()->cascadeOnDelete();
            $table->string('kind');                     // hint, step, example, explain, ask, stuck, feedback
            $table->string('source')->default('code');  // code, ai, fallback
            $table->text('student_text')->nullable();
            $table->text('content')->nullable();
            $table->timestamps();
        });

        Schema::create('misconception_observations', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->foreignId('tutor_session_id')->constrained()->cascadeOnDelete();
            $table->foreignId('working_line_id')->nullable()->constrained()->nullOnDelete();
            $table->string('skill_code');
            $table->string('code');
            $table->string('confidence');
            $table->string('evidence');                 // "2(3x − 4) = 10 → 6x − 4 = 10"
            $table->timestamps();
            $table->index(['user_id', 'code']);
        });

        Schema::create('tutor_checks', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->foreignId('tutor_session_id')->nullable()->constrained()->cascadeOnDelete();
            $table->string('skill_code');
            $table->string('reason')->nullable();       // misconception that prompted it
            $table->string('kind');                     // number, expression, equation
            $table->string('prompt');
            $table->string('answer');
            $table->string('given')->nullable();
            $table->boolean('correct')->nullable();
            $table->timestamps();
        });

        Schema::create('skill_records', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->string('skill_code');
            $table->string('state')->default('not_assessed');
            $table->unsignedInteger('unaided_successes')->default(0);
            $table->unsignedInteger('assisted_successes')->default(0);
            $table->unsignedInteger('checks_passed')->default(0);
            $table->unsignedInteger('checks_failed')->default(0);
            $table->timestamp('first_independent_at')->nullable();
            $table->timestamp('last_unaided_at')->nullable();
            $table->timestamp('last_assisted_at')->nullable();
            $table->timestamps();
            $table->unique(['user_id', 'skill_code']);
        });

        Schema::create('ai_calls', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->nullable()->constrained()->nullOnDelete();
            $table->string('purpose');                  // explain, steps, photo
            $table->string('provider');
            $table->string('model');
            $table->unsignedInteger('input_tokens')->default(0);
            $table->unsignedInteger('output_tokens')->default(0);
            $table->unsignedInteger('ms')->default(0);
            $table->boolean('ok')->default(false);
            $table->string('error')->nullable();
            $table->timestamps();
            $table->index(['user_id', 'created_at']);
        });
    }

    public function down(): void
    {
        foreach (['ai_calls', 'skill_records', 'tutor_checks', 'misconception_observations', 'help_events', 'working_lines', 'tutor_sessions', 'skill_prerequisites', 'skills'] as $t) {
            Schema::dropIfExists($t);
        }
    }
};
