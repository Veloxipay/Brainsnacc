<?php

namespace App\Support;

use App\Models\Lesson;
use Illuminate\Support\Str;

/**
 * The topic taught like a teacher at the board: short spoken sentences ("say") with what goes on the board ("write")
 * and pictures, one step at a time. Read straight from database/content/teach/<level>/<subject>/<lesson>.json,
 * kept apart from the lesson files so adding one never reloads (and resets) a lesson's questions.
 *
 * File: {"title": "...", "frames": [{"heading": "...", "lines": [{"say": "...", "write": "...", "diagram": "diagrams/....svg", "alt": "...", "draw": {...}}]}]}
 * "draw" is a drawing that changes with the step (see Drawings), e.g. cubes taken off both sides of a balance.
 */
class TeachScript
{
    public static function path(Lesson $lesson): string
    {
        $unit = $lesson->module->unit;

        return database_path('content/teach/'.Str::slug($unit->level->short_name).'/'.$unit->subject->slug.'/'.Str::slug($lesson->title).'.json');
    }

    /** The topic as a narrated board video (made by video/board_video.py), if it has been made. */
    public static function videoPath(Lesson $lesson): ?string
    {
        $unit = $lesson->module->unit;
        $rel = 'videos/teach/'.Str::slug($unit->level->short_name).'/'.$unit->subject->slug.'/'.Str::slug($lesson->title).'.mp4';

        return is_file(public_path($rel)) ? $rel : null;
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
                $draw = isset($line['draw']) && is_array($line['draw']) ? Drawings::render($line['draw']) : null;
                if (! $say && ! $write && ! $diagram && ! $draw) {
                    continue;
                }
                $lines[] = ['say' => $say ?: null, 'write' => $write ?: null, 'diagram' => $diagram, 'alt' => isset($line['alt']) ? (string) $line['alt'] : null, 'draw' => $draw];
            }
            if ($lines) {
                $frames[] = ['heading' => trim((string) ($frame['heading'] ?? '')), 'lines' => $lines];
            }
        }

        return $frames ?: null;
    }
}
