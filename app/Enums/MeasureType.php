<?php

namespace App\Enums;

use App\Enums\Concerns\HasLabel;

enum MeasureType: string
{
    use HasLabel;

    case Quantitative = 'quantitative';
    case Qualitative = 'qualitative';
    case Boolean = 'boolean';
    case Narrative = 'narrative';
}
