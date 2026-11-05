<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/*
 * Links students to the parents and teachers who look after them.
 * One student can have a parent AND a teacher; a teacher can have many students.
 * Existing parent links (users.parent_id) are copied across.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('student_supervisors', function (Blueprint $table) {
            $table->id();
            $table->foreignId('supervisor_id')->constrained('users')->cascadeOnDelete();
            $table->foreignId('student_id')->constrained('users')->cascadeOnDelete();
            $table->timestamps();
            $table->unique(['supervisor_id', 'student_id']);
        });

        $now = now();
        DB::table('users')->whereNotNull('parent_id')->get(['id', 'parent_id'])->each(function ($u) use ($now) {
            DB::table('student_supervisors')->insertOrIgnore([
                'supervisor_id' => $u->parent_id, 'student_id' => $u->id, 'created_at' => $now, 'updated_at' => $now,
            ]);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('student_supervisors');
    }
};
