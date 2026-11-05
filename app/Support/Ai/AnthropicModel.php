<?php

namespace App\Support\Ai;

use App\Models\AiCall;
use App\Models\User;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

/** Claude, through Anthropic's Messages API. Logs every call (tokens, time, outcome) for cost tracking. */
class AnthropicModel implements AiModel
{
    public function enabled(): bool
    {
        return (bool) config('services.anthropic.key');
    }

    public function complete(string $purpose, string $system, array $content, ?User $user = null, int $maxTokens = 400): ?string
    {
        if (! $this->enabled()) {
            return null;
        }
        if ($user && AiCall::where('user_id', $user->id)->where('created_at', '>=', now()->startOfDay())->count() >= config('tutor.ai_daily_calls')) {
            return null; // daily cap reached: built-in help takes over
        }

        $model = config('services.anthropic.model');
        $blocks = array_map(fn ($b) => match ($b['type']) {
            'image' => ['type' => 'image', 'source' => ['type' => 'base64', 'media_type' => $b['mime'], 'data' => $b['data']]],
            'document' => ['type' => 'document', 'source' => ['type' => 'base64', 'media_type' => 'application/pdf', 'data' => $b['data']]],
            default => ['type' => 'text', 'text' => $b['text']],
        }, $content);

        $call = new AiCall(['user_id' => $user?->id, 'purpose' => $purpose, 'provider' => 'anthropic', 'model' => $model]);
        $started = microtime(true);
        try {
            $response = Http::withHeaders([
                'x-api-key' => config('services.anthropic.key'),
                'anthropic-version' => '2023-06-01',
            ])->timeout(30)->post('https://api.anthropic.com/v1/messages', [
                'model' => $model,
                'max_tokens' => $maxTokens,
                'system' => $system,
                'messages' => [['role' => 'user', 'content' => $blocks]],
            ]);
            $text = trim((string) $response->json('content.0.text'));
            $call->fill([
                'input_tokens' => (int) $response->json('usage.input_tokens'),
                'output_tokens' => (int) $response->json('usage.output_tokens'),
                'ok' => $response->successful() && $text !== '',
                'error' => $response->successful() ? null : 'HTTP '.$response->status(),
            ]);

            return $call->ok ? $text : null;
        } catch (\Throwable $e) {
            $call->error = mb_substr($e->getMessage(), 0, 250);
            Log::warning('AI call failed', ['purpose' => $purpose, 'error' => $e->getMessage()]);

            return null;
        } finally {
            $call->ms = (int) ((microtime(true) - $started) * 1000);
            $call->save();
        }
    }
}
