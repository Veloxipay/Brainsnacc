<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

// How much help (hints, explanations) a student used before answering, so "correct on your own" can be shown separately.
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('question_attempts', function (Blueprint $table) {
            $table->unsignedTinyInteger('help_used')->default(0)->after('is_correct');
        });
    }

    public function down(): void
    {
        Schema::table('question_attempts', function (Blueprint $table) {
            $table->dropColumn('help_used');
        });
    }
};
