<?php

namespace App\Console\Commands;

use App\Models\IntegrityDocument;
use App\Services\Integrity\Plagiarism\Fingerprinter;
use Illuminate\Console\Command;

class BackfillIntegrityCorpus extends Command
{
    protected $signature = 'integrity:backfill-corpus {--course= : Only backfill documents for this course ID}';

    protected $description = 'Fingerprint historical integrity documents that are missing corpus fingerprints';

    public function handle(Fingerprinter $fingerprinter): int
    {
        $query = IntegrityDocument::query()
            ->whereNotNull('extracted_text')
            ->whereDoesntHave('fingerprints');

        if ($courseId = $this->option('course')) {
            $query->where('course_id', $courseId);
        }

        $count = 0;
        $query->chunkById(50, function ($documents) use ($fingerprinter, &$count) {
            foreach ($documents as $document) {
                $fingerprinter->fingerprint($document);
                $count++;
            }
        });

        $this->info("Fingerprinted {$count} document(s).");

        return self::SUCCESS;
    }
}
