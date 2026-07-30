<?php

namespace App\Enums;

use App\Enums\Concerns\HasLabel;

/**
 * Distinguishes the light per-report narrative analysis from the deep
 * whole-evaluation performance analysis. Both jobs write to the same
 * ai_analyses row keyed on (narrative_report_id, provider) — without this,
 * running one after the other on the same evaluation silently overwrites
 * the first result with the second.
 */
enum AiAnalysisType: string
{
    use HasLabel;

    case Narrative   = 'narrative';
    case Performance = 'performance';
}
