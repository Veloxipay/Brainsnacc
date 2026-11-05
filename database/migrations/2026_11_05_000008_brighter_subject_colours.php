<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

// Brighter subject colours to match the Brainsnacc logo (blue = Chemistry, orange = Biology).
return new class extends Migration
{
    public function up(): void
    {
        DB::table('subjects')->where('slug', 'chemistry')->update(['color' => '#2F6BEA', 'tint' => '#E8F0FE']);
        DB::table('subjects')->where('slug', 'biology')->update(['color' => '#E07A12', 'tint' => '#FFF1E3']);
    }

    public function down(): void
    {
        DB::table('subjects')->where('slug', 'chemistry')->update(['color' => '#2456C7', 'tint' => '#E8EEFB']);
        DB::table('subjects')->where('slug', 'biology')->update(['color' => '#B4540A', 'tint' => '#FBEEE3']);
    }
};
