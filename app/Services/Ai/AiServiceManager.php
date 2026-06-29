<?php

namespace App\Services\Ai;

use App\Enums\AiProvider;
use App\Models\Setting;
use Illuminate\Support\Facades\Log;

/**
 * AI Service Manager
 *
 * Unified gateway for all AI operations with automatic provider fallback.
 * Primary: Claude Pro (claude-opus-4-8)
 * Fallback: Google Gemini Pro
 */
class AiServiceManager
{
    /** @var array<string, AiAnalysisContract> */
    protected array $providers = [];

    public function __construct()
    {
        $enabled = (bool) Setting::get('ai_enabled', config('ai.enabled', false));
        if ($enabled) {
            $this->providers[AiProvider::ClaudePro->value] = new ClaudeProAnalyzer();
            $this->providers[AiProvider::GeminiPro->value] = new GoogleGeminiProAnalyzer();
        }

        if (app()->environment('local', 'testing')) {
            $this->providers[AiProvider::Mock->value] = new MockAnalyzer();
        }
    }

    // =========================================================================
    // Provider resolution
    // =========================================================================

    public function getAnalyzer(?string $provider = null): ?AiAnalysisContract
    {
        $enabled = (bool) Setting::get('ai_enabled', config('ai.enabled', false));
        if (!$enabled) {
            return null;
        }

        $defaultProvider = Setting::get('ai_default_provider', config('ai.default', 'claude_pro'));
        $name = $provider ?? $defaultProvider;

        if (!isset($this->providers[$name])) {
            Log::warning("AI provider not found: {$name}");
            return null;
        }

        $analyzer = $this->providers[$name];

        if (!$analyzer->isAvailable()) {
            Log::warning("AI provider not available: {$name}");

            // Fallback to Mock in local environment if key is missing or service not available
            if (app()->environment('local') && isset($this->providers[AiProvider::Mock->value])) {
                Log::info("Falling back to Mock AI analyzer in local environment.");
                return $this->providers[AiProvider::Mock->value];
            }

            return null;
        }

        return $analyzer;
    }

    /**
     * Return the fallback provider slug (the first available provider
     * that isn't the primary one).
     */
    protected function fallbackProvider(?string $primary): ?string
    {
        $defaultProvider = Setting::get('ai_default_provider', config('ai.default', 'claude_pro'));
        foreach ($this->providers as $slug => $analyzer) {
            if ($slug !== ($primary ?? $defaultProvider) && $analyzer->isAvailable()) {
                return $slug;
            }
        }
        return null;
    }

    // =========================================================================
    // Public analysis methods
    // =========================================================================

    public function analyzeNarrative(string $narrative, ?string $provider = null): array
    {
        return $this->dispatch(
            fn (AiAnalysisContract $ai) => $ai->analyzeNarrative($narrative),
            'analyzeNarrative',
            $provider
        );
    }

    public function analyzeConductIssue(
        string  $description,
        string  $type,
        string  $severity,
        ?string $provider = null
    ): array {
        return $this->dispatch(
            fn (AiAnalysisContract $ai) => $ai->analyzeConductIssue($description, $type, $severity),
            'analyzeConductIssue',
            $provider
        );
    }

    public function suggestInterventions(array $evaluationData, ?string $provider = null): array
    {
        return $this->dispatch(
            fn (AiAnalysisContract $ai) => $ai->suggestInterventions($evaluationData),
            'suggestInterventions',
            $provider
        );
    }

    /**
     * Deep performance analysis using all evaluation parameters.
     * $context should come from PerformanceContextBuilder::build().
     */
    public function analyzePerformance(array $context, ?string $provider = null): array
    {
        return $this->dispatch(
            fn (AiAnalysisContract $ai) => $ai->analyzePerformance($context),
            'analyzePerformance',
            $provider
        );
    }

    // =========================================================================
    // Utility
    // =========================================================================

    public function isEnabled(): bool
    {
        return config('ai.enabled', false);
    }

    /** @return array<string, AiAnalysisContract> */
    public function getAvailableProviders(): array
    {
        return array_filter($this->providers, fn ($a) => $a->isAvailable());
    }

    // =========================================================================
    // Internal dispatcher with automatic fallback
    // =========================================================================

    /**
     * Run $operation against the requested provider; on failure, try the fallback
     * provider once before giving up.
     */
    protected function dispatch(callable $operation, string $label, ?string $provider = null): array
    {
        $analyzer = $this->getAnalyzer($provider);

        if (!$analyzer) {
            return ['success' => false, 'error' => 'AI service not available'];
        }

        try {
            $result = $operation($analyzer);

            if ($result['success']) {
                return $result;
            }

            // Provider returned a failure — try fallback if this was the default
            $fallbackEnabled = (bool) Setting::get('ai_fallback_enabled', config('ai.fallback.enabled', true));
            if ($provider === null && $fallbackEnabled) {
                $fb = $this->fallbackProvider($provider);
                if ($fb) {
                    Log::info("AI primary failed, trying fallback provider: {$fb}", ['label' => $label]);
                    return $this->dispatch($operation, $label, $fb);
                }
            }

            return $result;

        } catch (\Exception $e) {
            Log::error("AI dispatch exception [{$label}]", ['error' => $e->getMessage()]);

            $fallbackEnabled = (bool) Setting::get('ai_fallback_enabled', config('ai.fallback.enabled', true));
            if ($provider === null && $fallbackEnabled) {
                $fb = $this->fallbackProvider($provider);
                if ($fb) {
                    Log::info("AI exception, trying fallback provider: {$fb}", ['label' => $label]);
                    return $this->dispatch($operation, $label, $fb);
                }
            }

            return ['success' => false, 'error' => "{$label} failed: " . $e->getMessage()];
        }
    }
}
