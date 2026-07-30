<?php

namespace App\Events;

use App\Models\IntegrityDocument;
use App\Models\IntegrityReport;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

/**
 * Fired by RunIntegrityAnalysis (Phase 3) once a document's AI-detection
 * report has been persisted. Phase 4's corpus fingerprinting listens for
 * this to fingerprint every newly-completed document automatically.
 */
class IntegrityReportCompleted
{
    use Dispatchable, SerializesModels;

    public function __construct(
        public readonly IntegrityDocument $document,
        public readonly IntegrityReport $report,
    ) {}
}
