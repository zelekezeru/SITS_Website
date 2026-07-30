<?php

return [
    /*
    |--------------------------------------------------------------------------
    | Detection thresholds
    |--------------------------------------------------------------------------
    */

    'flag_threshold' => env('INTEGRITY_FLAG_THRESHOLD', 70),
    'min_words' => 150,
    'max_words' => 30000,
    'chunk_words' => 4000,
    'chunk_overlap_words' => 200,

    /*
    |--------------------------------------------------------------------------
    | Composite scorer weights
    |--------------------------------------------------------------------------
    | Statistical vs Claude-analysis blend. Must sum to 1.0.
    */

    'weights' => [
        'statistical' => 0.45,
        'claude' => 0.55,
    ],

    /*
    |--------------------------------------------------------------------------
    | Rate / cost controls
    |--------------------------------------------------------------------------
    */

    'daily_quota' => env('INTEGRITY_DAILY_QUOTA', 50),

    // Multiplier applied to quota consumption for the web-source plagiarism
    // check (Phase 4.5) — a single "Check published sources" click on a
    // multi-passage document is several Claude calls, not one.
    'web_check_quota_weight' => 5,
    'web_check_cache_days' => 30,

    /*
    |--------------------------------------------------------------------------
    | Model
    |--------------------------------------------------------------------------
    | Reuses the SITS Claude Pro provider config/key — no second key path.
    */

    'claude_model' => env('INTEGRITY_CLAUDE_MODEL', config('ai.providers.claude_pro.model', 'claude-opus-5')),

    /*
    |--------------------------------------------------------------------------
    | Statistical signal 5: generic transition density stoplist
    |--------------------------------------------------------------------------
    | Editable — instructor feedback is expected to evolve this list over time.
    */

    'transition_stoplist' => [
        'furthermore', 'moreover', 'in conclusion', 'it is important to note',
        'delve', 'additionally', 'overall', "in today's world",
        'plays a crucial role', 'in summary', 'it is worth noting',
        'on the other hand', 'in essence', 'ultimately', 'ever-evolving',
        'in the realm of', 'a testament to', 'navigate the complexities',
    ],

    /*
    |--------------------------------------------------------------------------
    | Statistical signal baselines
    |--------------------------------------------------------------------------
    | Default calibration for graduate theological writing. Per-signal
    | mean/stddev used to compute z-scores. Recalculated by
    | `php artisan integrity:recalibrate` from documents reviewers have
    | marked `cleared` (human-confirmed).
    */

    'baselines' => [
        'burstiness' => ['mean' => 0.55, 'stddev' => 0.18],
        'sentence_length_uniformity' => ['mean' => 0.45, 'stddev' => 0.15],
        'type_token_ratio' => ['mean' => 0.48, 'stddev' => 0.08],
        'ngram_repetition' => ['mean' => 3.5, 'stddev' => 2.0],
        'transition_density' => ['mean' => 2.0, 'stddev' => 1.5],
        'em_dash_rate' => ['mean' => 0.5, 'stddev' => 0.8],
        'paragraph_uniformity' => ['mean' => 0.4, 'stddev' => 0.2],
        'sentence_opener_diversity' => ['mean' => 0.6, 'stddev' => 0.15],
        'personal_voice_markers' => ['mean' => 1.2, 'stddev' => 1.0],
        'list_structure_density' => ['mean' => 1.0, 'stddev' => 1.5],
        'readability_delta' => ['mean' => 0.0, 'stddev' => 8.0],
    ],
];
