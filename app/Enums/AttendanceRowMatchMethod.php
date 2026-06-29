<?php

namespace App\Enums;

use App\Enums\Concerns\HasLabel;

enum AttendanceRowMatchMethod: string
{
    use HasLabel;

    case DeviceCode = 'device_code';
    case NameExact = 'name_exact';
    case NameFuzzy = 'name_fuzzy';
    case Manual = 'manual';

    public function label(): string
    {
        return match ($this) {
            self::DeviceCode => 'Auto-matched (device ID)',
            self::NameExact => 'Auto-matched (exact name)',
            self::NameFuzzy => 'Auto-matched (similar name)',
            self::Manual => 'Manually linked',
        };
    }
}
