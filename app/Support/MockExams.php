<?php

namespace App\Support;

use Illuminate\Support\Collection;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Str;

/** Brainsnacc practice exams in the VCE format, read from database/content/mock-exams/<subject>/<slug>.json. */
class MockExams
{
    /** @return Collection<string, array> keyed by slug */
    public static function all(): Collection
    {
        return once(function () {
            $dir = database_path('content/mock-exams');
            if (! File::isDirectory($dir)) {
                return collect();
            }

            return collect(File::glob($dir.'/*/*.json'))
                ->map(fn ($path) => json_decode(File::get($path), true))
                ->filter(fn ($e) => is_array($e) && ! empty($e['slug']) && ! empty($e['section_a']) && ! empty($e['section_b']))
                ->map(function ($e) {
                    $e['marks_a'] = count($e['section_a']);
                    $e['marks_b'] = collect($e['section_b'])->flatMap(fn ($q) => $q['parts'])->sum('marks');
                    $e['total'] = $e['marks_a'] + $e['marks_b'];

                    return $e;
                })
                // Only show complete papers in the official format (a half-written file stays hidden).
                ->filter(fn ($e) => [$e['marks_a'], $e['marks_b']] === (['chemistry' => [30, 90], 'biology' => [40, 80]][$e['subject'] ?? ''] ?? null))
                ->sortBy(fn ($e) => [$e['subject'], $e['slug']])
                ->keyBy('slug');
        });
    }

    public static function find(string $slug): ?array
    {
        return self::all()->get($slug);
    }

    public static function md(?string $text): string
    {
        return Str::markdown((string) $text, ['html_input' => 'strip', 'allow_unsafe_links' => false]);
    }

    /** Stable key for a Section B part, e.g. "3b". */
    public static function partKey(array $question, array $part): string
    {
        return $question['number'].$part['label'];
    }
}
