<?php

namespace App\Jobs;

use App\Enums\WritingReportType;
use App\Models\IntegrityDocument;
use App\Services\Integrity\Writing\WritingToolsService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;

class RunWritingTool implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public $tries = 2;

    public $timeout = 120;

    public function __construct(
        protected IntegrityDocument $document,
        protected WritingReportType $type,
        protected string $instructorNotes = '',
    ) {
        $this->onQueue('integrity');
    }

    public function handle(WritingToolsService $service): void
    {
        $document = $this->document->fresh();
        if (! $document) {
            return;
        }

        $result = match ($this->type) {
            WritingReportType::GRAMMAR => $service->grammar($document),
            WritingReportType::SUMMARY => $service->summarize($document),
            WritingReportType::FACTCHECK => $service->factCheck($document),
            WritingReportType::FEEDBACK => $service->feedbackAssistant($document, $document->report, $this->instructorNotes),
        };

        if (! $result['success']) {
            Log::channel('ai')->error('Writing tool job failed', [
                'document_id' => $document->id,
                'type' => $this->type->value,
                'error' => $result['error'] ?? 'unknown error',
            ]);

            $this->fail(new \RuntimeException($result['error'] ?? 'Writing tool failed'));
        }
    }
}
