<?php

namespace App\Enums;

enum IntegrityDocumentStatus: string
{
    case PENDING = 'pending';
    case PROCESSING = 'processing';
    case COMPLETE = 'complete';
    case FAILED = 'failed';
}
