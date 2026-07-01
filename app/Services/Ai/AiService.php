<?php

namespace App\Services\Ai;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

/**
 * Provider-aware AI client for the SITS Library — Claude (Anthropic Messages API)
 * or Gemini (OpenAI-compatible endpoint), chosen by `services.ai.default`.
 *
 * Used for smart cataloging (enriching sparse book metadata), and available for
 * semantic search / summaries. Gracefully no-ops (returns null) when no key is set.
 */
class AiService
{
    public function provider(): string
    {
        return (string) config('services.ai.default', 'claude');
    }

    public function enabled(): bool
    {
        return (bool) config('services.ai.'.$this->provider().'.key');
    }

    /**
     * Single-prompt completion against the configured provider.
     * Returns the model's text, or null when AI is disabled or the call fails.
     */
    public function complete(string $prompt, int $maxTokens = 600): ?string
    {
        if (! $this->enabled()) {
            return null;
        }

        try {
            return match ($this->provider()) {
                'gemini', 'openai' => $this->openAiCompatible($prompt, $maxTokens),
                default            => $this->claude($prompt, $maxTokens),
            };
        } catch (\Throwable $e) {
            Log::warning('AiService: completion failed', ['provider' => $this->provider(), 'error' => $e->getMessage()]);

            return null;
        }
    }

    /**
     * Ask the model for JSON and decode it (strips ```json fences). Null on failure.
     */
    public function json(string $prompt, int $maxTokens = 800): ?array
    {
        $raw = $this->complete($prompt.' Respond with ONLY minified JSON, no prose.', $maxTokens);
        if (! $raw) {
            return null;
        }
        $raw = trim(preg_replace('/^```(?:json)?|```$/m', '', trim($raw)));

        return json_decode($raw, true) ?: null;
    }

    // ── Claude (Anthropic native Messages API) ────────────────────────────────
    protected function claude(string $prompt, int $maxTokens): ?string
    {
        $cfg = config('services.ai.claude');

        $resp = Http::withHeaders([
            'x-api-key'         => $cfg['key'],
            'anthropic-version' => '2023-06-01',
        ])->timeout(30)->post(rtrim($cfg['base'], '/').'/messages', [
            'model'      => $cfg['model'],
            'max_tokens' => $maxTokens,
            'messages'   => [['role' => 'user', 'content' => $prompt]],
        ]);

        return $resp->json('content.0.text');
    }

    // ── Gemini / OpenAI-compatible chat/completions ──────────────────────────
    protected function openAiCompatible(string $prompt, int $maxTokens): ?string
    {
        $key = $this->provider() === 'gemini' ? 'gemini' : 'openai';
        $cfg = config("services.ai.$key") ?? config('services.openai');

        $resp = Http::withToken($cfg['key'])->timeout(30)
            ->post(rtrim($cfg['base'], '/').'/chat/completions', [
                'model'      => $cfg['model'],
                'max_tokens' => $maxTokens,
                'messages'   => [['role' => 'user', 'content' => $prompt]],
            ]);

        return $resp->json('choices.0.message.content');
    }
}
