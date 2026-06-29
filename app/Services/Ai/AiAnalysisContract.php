<?php

namespace App\Services\Ai;

/**
 * Base contract for AI analysis providers.
 *
 * Every method returns an array that always includes:
 *   - 'success' (bool)
 *   - 'error'   (string, only when success === false)
 */
interface AiAnalysisContract
{
    /**
     * Analyse a free-text narrative report.
     *
     * @return array{
     *   success: bool,
     *   summary_en?: string,
     *   summary_am?: string,
     *   kpi_scores?: array<string,int>,
     *   sentiment?: array{overall:string,confidence:float},
     *   risk_flags?: list<string>,
     *   strengths?: list<string>,
     *   areas_for_improvement?: list<string>,
     *   error?: string,
     * }
     */
    public function analyzeNarrative(string $narrative): array;

    /**
     * Analyse a conduct / disciplinary issue.
     *
     * @return array{
     *   success: bool,
     *   severity_assessment?: string,
     *   confidence?: float,
     *   risk_level?: string,
     *   suggested_actions?: list<string>,
     *   escalation_needed?: bool,
     *   investigation_required?: bool,
     *   warnings?: list<string>,
     *   error?: string,
     * }
     */
    public function analyzeConductIssue(string $description, string $type, string $severity): array;

    /**
     * Generate intervention / support suggestions from evaluation data.
     *
     * @return array{
     *   success: bool,
     *   interventions?: list<array{type:string,description:string,priority:string,timeline:string}>,
     *   support_measures?: list<string>,
     *   follow_up_frequency?: string,
     *   success_metrics?: list<string>,
     *   error?: string,
     * }
     */
    public function suggestInterventions(array $evaluationData): array;

    /**
     * Deep-analyse all evaluation parameters for a single employee in a period.
     * This uses extended thinking (when supported by the provider) for richer insight.
     *
     * $context is the structured array produced by PerformanceContextBuilder::build().
     *
     * @return array{
     *   success: bool,
     *   summary_en?: string,
     *   summary_am?: string,
     *   kpi_scores_json?: array<string,array{score:int,evidence:string}>,
     *   sentiment?: array{overall:string,confidence:float,detail:string},
     *   risk_flags?: list<string>,
     *   strengths?: list<string>,
     *   development_areas?: list<string>,
     *   interventions?: list<array{type:string,description:string,priority:string,timeline:string}>,
     *   increment_recommendation?: array{justified:bool,rationale:string},
     *   tokens_used?: int,
     *   error?: string,
     * }
     */
    public function analyzePerformance(array $context): array;

    /**
     * Return true when the provider is configured and ready to accept requests.
     */
    public function isAvailable(): bool;

    /**
     * Return the provider slug (e.g. 'claude_pro', 'gemini_pro').
     */
    public function getProvider(): string;
}
