<?php

namespace App\Enums;

use App\Enums\Concerns\HasLabel;

enum AiProvider: string
{
    use HasLabel;

    case Claude = 'claude';
    case ClaudePro = 'claude_pro';
    case Gemini = 'gemini';
    case GeminiPro = 'gemini_pro';
    case Mock = 'mock';
}
