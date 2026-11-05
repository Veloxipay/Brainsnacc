<?php

namespace Database\Seeders;

use App\Models\Skill;
use App\Tutor\Skills;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

/** Copies the tutor's skill list (app/Tutor/Skills.php) into the database. Safe to re-run. */
class SkillSeeder extends Seeder
{
    public function run(): void
    {
        foreach (Skills::all() as $code => $s) {
            Skill::updateOrCreate(['code' => $code], [
                'name' => $s['name'], 'year' => $s['year'], 'strand' => $s['strand'], 'objective' => $s['objective'],
            ]);
            DB::table('skill_prerequisites')->where('skill_code', $code)->delete();
            foreach ($s['prereqs'] as $p) {
                DB::table('skill_prerequisites')->insert(['skill_code' => $code, 'prerequisite_code' => $p]);
            }
        }
    }
}
