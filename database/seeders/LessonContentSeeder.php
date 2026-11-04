<?php

namespace Database\Seeders;

use App\Models\Lesson;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\File;

/**
 * Loads finished lessons from database/content/**.json and makes them live.
 *
 * Each file:
 * {
 *   "level": "Year 12", "subject": "chemistry", "unit": "Unit 3",
 *   "title": "Galvanic cells",           // must match the lesson title in CourseSeeder
 *   "summary": "...", "minutes": 15,
 *   "body": "Markdown notes…",
 *   "questions": [{"prompt": "...", "options": {"A": "...", "B": "..."}, "answer": "B", "explanation": "..."}]
 * }
 *
 * A lesson is only filled once (when it has no notes yet), so edits made in the admin screens are never overwritten.
 */
class LessonContentSeeder extends Seeder
{
    public function run(): void
    {
        $dir = database_path('content');
        if (! File::isDirectory($dir)) {
            return;
        }

        $loaded = 0;
        foreach (File::allFiles($dir) as $file) {
            if ($file->getExtension() !== 'json') {
                continue;
            }

            $data = json_decode($file->getContents(), true);
            if (! is_array($data) || empty($data['title']) || empty($data['body'])) {
                $this->command?->warn('Skipped (bad file): '.$file->getRelativePathname());
                continue;
            }

            $lesson = Lesson::where('title', $data['title'])
                ->whereHas('module.unit', fn ($q) => $q->where('code', $data['unit'])
                    ->whereHas('level', fn ($l) => $l->where('short_name', $data['level']))
                    ->whereHas('subject', fn ($s) => $s->where('slug', $data['subject'])))
                ->first();

            if (! $lesson) {
                $this->command?->warn('No matching lesson for: '.$file->getRelativePathname());
                continue;
            }

            if (filled($lesson->body)) {
                continue; // already written — keep any edits
            }

            DB::transaction(function () use ($lesson, $data) {
                $lesson->update([
                    'summary' => $data['summary'] ?? null,
                    'minutes' => (int) ($data['minutes'] ?? 15),
                    'body' => $data['body'],
                    'status' => 'published',
                ]);

                foreach (array_values($data['questions'] ?? []) as $i => $q) {
                    if (empty($q['prompt']) || empty($q['options']) || empty($q['answer'])) {
                        continue;
                    }
                    $lesson->questions()->create([
                        'type' => 'mcq',
                        'prompt' => $q['prompt'],
                        'options' => $q['options'],
                        'answer' => strtoupper($q['answer']),
                        'explanation' => $q['explanation'] ?? null,
                        'sort' => $i + 1,
                    ]);
                }
            });

            $loaded++;
        }

        $this->command?->info("Lessons loaded: {$loaded}");
    }
}
