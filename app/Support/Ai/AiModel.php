<?php

namespace App\Support\Ai;

use App\Models\User;

/**
 * Any AI provider the app talks to. Swap the provider by binding a different class in AppServiceProvider.
 * Every method returns null when AI is off, over the daily cap, slow or failing, so callers always have a fallback.
 */
interface AiModel
{
    /**
     * @param  list<array>  $content  message blocks: ['type' => 'text', 'text' => …] or ['type' => 'image', 'mime' => …, 'data' => base64] or ['type' => 'document', 'data' => base64 PDF]
     */
    public function complete(string $purpose, string $system, array $content, ?User $user = null, int $maxTokens = 400): ?string;

    public function enabled(): bool;
}
