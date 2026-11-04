<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Str;

class Lesson extends Model
{
    protected $fillable = ['module_id', 'title', 'summary', 'body', 'content_hash', 'minutes', 'status', 'sort'];

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
}
