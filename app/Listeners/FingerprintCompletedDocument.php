<?php

namespace App\Listeners;

use App\Events\IntegrityReportCompleted;
use App\Services\Integrity\Plagiarism\Fingerprinter;

/**
 * Automatically fingerprints every document once its AI-detection report
 * completes, so it's immediately searchable as part of the corpus for
 * future plagiarism checks — including a document scoring insufficient_text,
 * since it's still real prior-submission text worth matching against.
 */
class FingerprintCompletedDocument
{
    public function __construct(protected Fingerprinter $fingerprinter) {}

    public function handle(IntegrityReportCompleted $event): void
    {
        $this->fingerprinter->fingerprint($event->document);
    }
}
