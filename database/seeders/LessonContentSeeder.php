<?php

namespace Database\Seeders;

use App\Models\Lesson;
use App\Models\Question;
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
 *   "questions": [{"prompt": "...", "options": {"A": "...", "B": "..."}, "answer": "B", "explanation": "...",
 *                  "difficulty": "easy|medium|hard|challenge",          // optional
 *                  "steps": ["Step 1 — reason", "Step 2 — reason"],      // optional: the worked steps
 *                  "mistakes": {"A": "Step 2: …", "C": "…"}}]            // optional: what goes wrong for each wrong option
 * }
 *
 * A lesson is filled when it has no notes yet, or when its file has changed since it was last loaded.
 * Lessons edited by hand in the admin screens (content_hash = null with notes present) are never overwritten.
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
        $failed = 0;
        foreach (File::allFiles($dir) as $file) {
            if ($file->getExtension() !== 'json' || str_starts_with($file->getRelativePathname(), 'mock-exams') || $file->getFilename() === 'maths-manifest.json') {
                continue; // practice exams are read straight from their files, not loaded as lessons
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

            $hash = hash('sha256', $file->getContents());

            if (filled($lesson->body) && ($lesson->content_hash === null || $lesson->content_hash === $hash)) {
                continue; // hand-edited, or this exact version is already loaded
            }

            try {
            DB::transaction(function () use ($lesson, $data, $hash) {
                $lesson->update([
                    'summary' => $data['summary'] ?? null,
                    'minutes' => (int) ($data['minutes'] ?? 15),
                    'body' => $data['body'],
                    'video' => $data['video'] ?? null,
                    'kind' => ($data['kind'] ?? 'lesson') === 'test' ? 'test' : 'lesson',
                    'content_hash' => $hash,
                    'status' => 'published',
                ]);
                $lesson->questions()->delete(); // replaced by the file's questions

                // Easy questions first, then medium, hard and challenge (file order kept within a level).
                $levels = array_keys(Question::LEVELS);
                $questions = collect(array_values($data['questions'] ?? []))
                    ->sortBy(function ($q, $i) use ($levels) {
                        $rank = array_search($q['difficulty'] ?? 'medium', $levels, true);

                        return [$rank === false ? 1 : $rank, $i];
                    })
                    ->values();

                foreach ($questions as $i => $q) {
                    if (empty($q['prompt']) || empty($q['options']) || empty($q['answer'])) {
                        continue;
                    }
                    $lesson->questions()->create([
                        'type' => 'mcq',
                        'difficulty' => in_array($q['difficulty'] ?? null, $levels, true) ? $q['difficulty'] : null,
                        'topic' => isset($q['topic']) ? mb_substr((string) $q['topic'], 0, 190) : null,
                        'prompt' => $q['prompt'],
                        'diagram' => isset($q['diagram']) && preg_match('#^diagrams/[a-z0-9/_-]+\.svg$#', $q['diagram']) ? $q['diagram'] : null,
                        'diagram_alt' => isset($q['diagram_alt']) ? mb_substr((string) $q['diagram_alt'], 0, 300) : null,
                        'options' => $q['options'],
                        'answer' => strtoupper($q['answer']),
                        'explanation' => $q['explanation'] ?? null,
                        'steps' => ! empty($q['steps']) ? array_values($q['steps']) : null,
                        'mistakes' => ! empty($q['mistakes']) ? array_change_key_case($q['mistakes'], CASE_UPPER) : null,
                        'sort' => $i + 1,
                    ]);
                }
            });
            } catch (\Throwable $e) {
                // One bad file must never stop the other lessons from loading.
                $failed++;
                $this->command?->error('Could not load '.$file->getRelativePathname().': '.\Illuminate\Support\Str::limit($e->getMessage(), 200));
                continue;
            }

            $loaded++;
        }

        $this->command?->info("Lessons loaded: {$loaded}".($failed ? " · failed: {$failed}" : ''));
    }
}
