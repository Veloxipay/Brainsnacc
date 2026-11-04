<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

/*
 * The two sample lessons (Galvanic cells, Enzymes) were written in code, not as files,
 * so they were treated like hand-edited lessons. Full versions now exist as files —
 * mark the samples so the full versions replace them on the next load.
 */
return new class extends Migration
{
    public function up(): void
    {
        DB::table('lessons')
            ->whereIn('title', ['Galvanic cells', 'Enzymes'])
            ->whereNull('content_hash')
            ->update(['content_hash' => 'sample-written-in-code']);
    }

    public function down(): void
    {
        //
    }
};
