<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/*
 * Remembers which version of a lesson file was loaded, so corrected lesson files
 * replace the old version on the next deploy. Set to null when the admin edits a lesson,
 * so hand edits are never overwritten.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('lessons', function (Blueprint $table) {
            $table->string('content_hash', 64)->nullable()->after('body');
        });

        // Lessons already loaded from files (everything except the two written in code)
        // get a placeholder, so the latest version of their file is loaded once.
        \Illuminate\Support\Facades\DB::table('lessons')
            ->whereNotNull('body')
            ->whereNotIn('title', ['Galvanic cells', 'Enzymes'])
            ->update(['content_hash' => 'loaded-before-tracking']);
    }

    public function down(): void
    {
        Schema::table('lessons', function (Blueprint $table) {
            $table->dropColumn('content_hash');
        });
    }
};
