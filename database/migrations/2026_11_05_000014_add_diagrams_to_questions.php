<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

// A question can show a diagram (an SVG under public/diagrams) with a text description for screen readers.
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('questions', function (Blueprint $table) {
            $table->string('diagram')->nullable()->after('prompt');
            $table->string('diagram_alt', 300)->nullable()->after('diagram');
        });
    }

    public function down(): void
    {
        Schema::table('questions', fn (Blueprint $t) => $t->dropColumn(['diagram', 'diagram_alt']));
    }
};
