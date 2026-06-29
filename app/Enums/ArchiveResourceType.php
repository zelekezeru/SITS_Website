<?php

namespace App\Enums;

use App\Enums\Concerns\HasLabel;

enum ArchiveResourceType: string
{
    use HasLabel;

    case Image = 'image';
    case File = 'file';
    case WebLink = 'web_link';
    case Other = 'other';

    public function label(): string
    {
        return match ($this) {
            self::Image => 'Image',
            self::File => 'File',
            self::WebLink => 'Web Link',
            self::Other => 'Other',
        };
    }
}
