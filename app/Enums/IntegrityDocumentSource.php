<?php

namespace App\Enums;

enum IntegrityDocumentSource: string
{
    case PASTE = 'paste';
    case UPLOAD = 'upload';
}
