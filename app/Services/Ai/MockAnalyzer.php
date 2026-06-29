<?php

namespace App\Services\Ai;

/**
 * Mock AI Analyzer for local development and testing.
 * Returns realistic-looking evaluation, narrative, and conduct insights immediately.
 */
class MockAnalyzer implements AiAnalysisContract
{
    public function isAvailable(): bool
    {
        return true;
    }

    public function getProvider(): string
    {
        return 'mock';
    }

    public function analyzeNarrative(string $narrative): array
    {
        return [
            'success'                => true,
            'provider'               => 'mock',
            'model'                  => 'mock-model',
            'summary_en'             => 'Mock: The employee demonstrates a strong commitment to core values, works collaboratively, and consistently meets project milestones. They show proactive problem-solving skills.',
            'summary_am'             => 'የአምሀርኛ ማጠቃለያ: ሰራተኛው ለዋና እሴቶች ያለውን ጠንካራ ቁርጠኝነት ያሳያል፣ በጋራ ይሰራል እና የፕሮጀክት ግቦችን በቋሚነት ያሳካል። ቅድመ-ተነሳሽነት ያለው ችግር የመፍታት ችሎታዎችን ያሳያሉ።',
            'kpi_scores'             => [
                'Execution Rate'    => 90,
                'Team Collaboration' => 88,
                'Problem Solving'   => 85,
            ],
            'sentiment'              => [
                'overall'    => 'positive',
                'confidence' => 0.92,
            ],
            'risk_flags'             => ['High workload risk during crunch periods'],
            'strengths'              => ['Proactive planning', 'Strong cross-department collaboration'],
            'areas_for_improvement'  => ['Delegation of minor tasks to avoid burnout'],
        ];
    }

    public function analyzeConductIssue(string $description, string $type, string $severity): array
    {
        return [
            'success'                => true,
            'provider'               => 'mock',
            'model'                  => 'mock-model',
            'severity_assessment'    => 'minor',
            'confidence'             => 0.90,
            'risk_level'             => 'low',
            'suggested_actions'      => ['1-on-1 coaching session', 'Document issue for review during annual appraisal'],
            'escalation_needed'      => false,
            'investigation_required' => false,
            'warnings'               => ['Ensure alignment with institution guidelines'],
        ];
    }

    public function suggestInterventions(array $evaluationData): array
    {
        return [
            'success'              => true,
            'provider'             => 'mock',
            'model'                => 'mock-model',
            'interventions'        => [
                [
                    'type'        => 'training',
                    'description' => 'Advanced Project Planning & Resource Allocation',
                    'priority'    => 'medium',
                    'timeline'    => '2-4 weeks',
                ]
            ],
            'support_measures'     => ['Regular mentor meetings', 'Bi-weekly workload reviews'],
            'follow_up_frequency'  => 'monthly',
            'success_metrics'      => ['Improved task completion rates within scheduled timelines'],
        ];
    }

    public function analyzePerformance(array $context): array
    {
        $employeeName = $context['employee']['full_name_en'] ?? ($context['employee']['name'] ?? 'Employee');
        $position = $context['employee']['position'] ?? 'Staff Member';

        return [
            'success'                    => true,
            'provider'                   => 'mock',
            'model'                      => 'mock-model',
            'summary_en'                 => "Mock Performance Insight: {$employeeName} has demonstrated solid outcomes as a {$position}. Their ratings show steady progress, with high scores in team collaboration and domain expertise. A structured path for leadership training is recommended.",
            'summary_am'                 => "የአምሀርኛ ማጠቃለያ: {$employeeName} እንደ {$position} ጠንካራ ውጤቶችን አሳይቷል። ደረጃ አሰጣጣቸው በተለይም በቡድን ትብብር እና በስራ መስክ ሙያ ላይ ከፍተኛ ውጤቶችን በማስመዝገብ የተረጋጋ እድገትን ያሳያል። የተዋቀረ የአመራር ስልጠና ይመከራል።",
            'kpi_scores_json'            => [
                [
                    'kpi_id'    => 1,
                    'kpi_title' => 'Job Description Requirements',
                    'ai_score'  => 88,
                    'evidence'  => 'Maintained an average scoring rate of 88% on key deliverables.',
                ]
            ],
            'sentiment'                  => [
                'overall'    => 'positive',
                'confidence' => 0.94,
                'detail'     => 'The overall supervisor feedback and comments reflect high employee morale and organizational dedication.',
            ],
            'risk_flags'                 => ['Balance between administrative and technical delivery'],
            'strengths'                  => ['Technical proficiency', 'High adaptability to curriculum shifts'],
            'development_areas'          => ['Strategic institutional planning contributions'],
            'interventions'              => [
                [
                    'type'        => 'mentoring',
                    'description' => 'Mentorship under Vice President or a Senior Director',
                    'priority'    => 'high',
                    'timeline'    => '1-3 months',
                ]
            ],
            'increment_recommendation'   => [
                'justified' => true,
                'rationale' => 'Performance metrics and high rating profile justify salary grade increment.',
            ],
            'tokens_used'                => 120,
        ];
    }
}
