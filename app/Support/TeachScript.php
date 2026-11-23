<?php

namespace App\Support;

use App\Models\Lesson;
use Illuminate\Support\Str;

/**
 * The topic taught like a teacher at the board: short spoken sentences ("say") with what goes on the board ("write")
 * and pictures, one step at a time. Read straight from database/content/teach/<level>/<subject>/<lesson>.json,
 * kept apart from the lesson files so adding one never reloads (and resets) a lesson's questions.
 *
 * File: {"title": "...", "frames": [{"heading": "...", "lines": [{"say": "...", "write": "...", "diagram": "diagrams/....svg", "alt": "..."}]}]}
 */
class TeachScript
{
    public static function path(Lesson $lesson): string
    {
        $unit = $lesson->module->unit;

        return database_path('content/teach/'.Str::slug($unit->level->short_name).'/'.$unit->subject->slug.'/'.Str::slug($lesson->title).'.json');
    }

    /** @return array<int, array{heading: string, lines: array<int, array{say: ?string, write: ?string, diagram: ?string, alt: ?string}>}>|null */
    public static function for(Lesson $lesson): ?array
    {
        $file = self::path($lesson);
        if (! is_file($file)) {
            return null;
        }
        $data = json_decode((string) file_get_contents($file), true);
        if (! is_array($data) || empty($data['frames']) || ! is_array($data['frames'])) {
            return null;
        }

        $frames = [];
        foreach ($data['frames'] as $frame) {
            $lines = [];
            foreach ((array) ($frame['lines'] ?? []) as $line) {
                $diagram = isset($line['diagram']) && preg_match('#^/?diagrams/[a-z0-9/_-]+\.svg$#', (string) $line['diagram']) ? ltrim($line['diagram'], '/') : null;
                $say = isset($line['say']) ? trim((string) $line['say']) : null;
                $write = isset($line['write']) ? trim((string) $line['write']) : null;
                if (! $say && ! $write && ! $diagram) {
                    continue;
                }
                $lines[] = ['say' => $say ?: null, 'write' => $write ?: null, 'diagram' => $diagram, 'alt' => isset($line['alt']) ? (string) $line['alt'] : null];
            }
            if ($lines) {
                $frames[] = ['heading' => trim((string) ($frame['heading'] ?? '')), 'lines' => $lines];
            }
        }

        return $frames ?: null;
    }
}
