<?php

namespace App\Notifications;

use App\Models\IntegrityDocument;
use Illuminate\Notifications\Notification;

class IntegrityAnalysisFailed extends Notification
{
    public function __construct(protected IntegrityDocument $document, protected string $reason) {}

    public function via(object $notifiable): array
    {
        return ['database'];
    }

    public function toDatabase(object $notifiable): array
    {
        return [
            'type' => 'integrity_analysis_failed',
            'document_id' => $this->document->id,
            'document_uuid' => $this->document->uuid,
            'title' => $this->document->title,
            'reason' => $this->reason,
        ];
    }
}
