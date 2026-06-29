<?php

namespace App\Enums;

use App\Enums\Concerns\HasLabel;

enum AttendanceSource: string
{
    use HasLabel;

    case DeviceFetch = 'device_fetch';
    case ExcelImport = 'excel_import';
    case Manual = 'manual';
}
