<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Str;

class Lesson extends Model
{
    protected $fillable = ['module_id', 'title', 'summary', 'body', 'video', 'content_hash', 'minutes', 'status', 'sort'];

    public function module(): BelongsTo
    {
        return $this->belongsTo(Module::class);
    }

    public function questions(): HasMany
    {
        return $this->hasMany(Question::class)->orderBy('sort');
    }

    public function scopePublished(Builder $query): Builder
    {
        return $query->where('status', 'published');
    }

    public function isPublished(): bool
    {
        return $this->status === 'published';
    }

    /** Lesson notes are written in Markdown; raw HTML is stripped for safety. */
    public function bodyHtml(): string
    {
        return Str::markdown($this->body ?? '', [
            'html_input' => 'strip',
            'allow_unsafe_links' => false,
        ]);
    }

    /** The short sections worth re-reading when a question goes wrong, as HTML keyed by heading. */
    public function helpSections(): array
    {
        $parts = preg_split('/^## +(.+)$/m', (string) $this->body, -1, PREG_SPLIT_DELIM_CAPTURE);
        $out = [];
        for ($i = 1; $i < count($parts) - 1; $i += 2) {
            $heading = trim($parts[$i]);
            if (in_array($heading, ['Think of it like this', 'Key ideas', 'Common mistakes', 'Tips to remember'], true)) {
                $out[$heading] = Str::markdown(trim($parts[$i + 1]), ['html_input' => 'strip', 'allow_unsafe_links' => false]);
            }
        }

        return $out;
    }

    /**
     * The lesson split into slides for the slide-show view: one slide per "##" section,
     * with long sections split again at their "###" sub-headings so each slide fits on one screen.
     *
     * @return array<int, array{title: string, html: string}>
     */
    public function slides(): array
    {
        $md = fn (string $text) => Str::markdown(trim($text), ['html_input' => 'strip', 'allow_unsafe_links' => false]);
        $parts = preg_split('/^## +(.+)$/m', (string) $this->body, -1, PREG_SPLIT_DELIM_CAPTURE);
        $slides = [];

        if (trim($parts[0]) !== '') {
            $slides[] = ['title' => 'Introduction', 'section' => 'Introduction', 'html' => $md($parts[0])];
        }

        for ($i = 1; $i < count($parts) - 1; $i += 2) {
            $heading = trim($parts[$i]);
            $text = $parts[$i + 1];
            $chunks = preg_split('/^(?=### )/m', $text);
            $intro = str_starts_with(ltrim($chunks[0]), '### ') ? '' : array_shift($chunks);

            if (str_word_count(strip_tags($text)) <= 260 || count($chunks) < 2) {
                $slides[] = ['title' => $heading, 'section' => $heading, 'html' => $md($text)];
                continue;
            }

            // Group sub-sections into slides of roughly 250 words.
            $groups = [];
            $current = trim($intro);
            foreach ($chunks as $chunk) {
                if ($current !== '' && str_word_count($current.' '.$chunk) > 250) {
                    $groups[] = $current;
                    $current = '';
                }
                $current .= "\n\n".$chunk;
            }
            if (trim($current) !== '') {
                $groups[] = $current;
            }

            foreach ($groups as $n => $group) {
                $first = preg_match('/^### +(.+)$/m', $group, $m) ? trim($m[1]) : null;
                $slides[] = [
                    'title' => count($groups) > 1 && $first ? $heading.' · '.$first : $heading,
                    'section' => $heading,
                    'html' => $md($group),
                ];
            }
        }

        return $slides;
    }
}
