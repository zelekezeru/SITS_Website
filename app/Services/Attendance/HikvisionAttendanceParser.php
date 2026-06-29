<?php

namespace App\Services\Attendance;

use Carbon\Carbon;
use PhpOffice\PhpSpreadsheet\IOFactory;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;
use RuntimeException;

/**
 * Parses a HikVision "Attendance Summary" export.
 *
 * The device's template uses a fixed column layout (Employee ID, Name,
 * Department, Work Duration Standard/Actual, Late Times/Duration, Leave Early
 * Times/Duration, OverTime Normal/Special, Lack Times/Duration, Attendance
 * days St/Ac, Absent(Days), Remarks) under a two-row merged header that is
 * brittle to parse by text, so data rows are instead detected directly: the
 * first column holding a positive integer (the device Employee ID) with a
 * non-empty Name beside it marks the start of data, and rows are read until
 * that pattern breaks.
 */
class HikvisionAttendanceParser
{
    private const COL_EMPLOYEE_ID = 'A';
    private const COL_NAME = 'B';
    private const COL_DEPARTMENT = 'C';
    private const COL_WORK_STANDARD = 'D';
    private const COL_WORK_ACTUAL = 'E';
    private const COL_LATE_TIMES = 'F';
    private const COL_LATE_MINUTES = 'G';
    private const COL_LEAVE_EARLY_TIMES = 'H';
    private const COL_LEAVE_EARLY_MINUTES = 'I';
    private const COL_OT_NORMAL = 'J';
    private const COL_OT_SPECIAL = 'K';
    private const COL_LACK_TIMES = 'L';
    private const COL_LACK_MINUTES = 'M';
    private const COL_ATTENDANCE_DAYS = 'N';
    private const COL_ABSENT_DAYS = 'O';
    private const COL_REMARKS = 'P';

    /**
     * @return array{rows: array<int, array<string, mixed>>, period: array{start: ?Carbon, end: ?Carbon}}
     */
    public function parse(string $path): array
    {
        $spreadsheet = $this->load($path);
        $sheet = $spreadsheet->getActiveSheet();

        $this->assertExpectedTemplate($sheet);

        return [
            'rows' => $this->readRows($sheet),
            'period' => $this->readMadeDateRange($sheet),
        ];
    }

    /**
     * HikVision device exports are frequently old-format .xls (BIFF) files whose
     * code-page strings contain truncated multibyte sequences. PhpSpreadsheet
     * recovers from these on its own (it falls back to mb_convert_encoding), but
     * the underlying iconv() call still emits an E_WARNING which Laravel's error
     * handler escalates into a fatal ErrorException. We swallow only that benign
     * iconv warning during the load so the library's own fallback can proceed;
     * every other error is passed straight through to the previous handler.
     */
    private function load(string $path): \PhpOffice\PhpSpreadsheet\Spreadsheet
    {
        $previous = set_error_handler(
            static function (int $errno, string $errstr) use (&$previous) {
                if (str_contains($errstr, 'iconv')) {
                    return true; // benign — let PhpSpreadsheet fall back internally
                }

                return $previous ? $previous(...func_get_args()) : false;
            },
            E_WARNING
        );

        try {
            return IOFactory::load($path);
        } catch (\PhpOffice\PhpSpreadsheet\Reader\Exception $e) {
            throw new RuntimeException('Could not read the uploaded file as an Excel workbook. Make sure it is the original .xls/.xlsx exported by the attendance device.', 0, $e);
        } finally {
            restore_error_handler();
        }
    }

    private function assertExpectedTemplate(Worksheet $sheet): void
    {
        $found = false;

        foreach ($sheet->getRowIterator(1, 10) as $row) {
            foreach ($row->getCellIterator() as $cell) {
                if (str_contains(strtolower((string) $cell->getValue()), 'employee id')) {
                    $found = true;
                    break 2;
                }
            }
        }

        if (! $found) {
            throw new RuntimeException(
                'This file does not look like a HikVision attendance export (no "Employee ID" header found in the first 10 rows).'
            );
        }
    }

    /** @return array<int, array<string, mixed>> */
    private function readRows(Worksheet $sheet): array
    {
        $rows = [];
        $highestRow = $sheet->getHighestRow();

        for ($r = 1; $r <= $highestRow; $r++) {
            $employeeId = trim((string) $sheet->getCell(self::COL_EMPLOYEE_ID.$r)->getCalculatedValue());
            $name = trim((string) $sheet->getCell(self::COL_NAME.$r)->getCalculatedValue());

            if ($employeeId === '' || ! ctype_digit($employeeId) || $name === '') {
                continue;
            }

            [$standardDays, $actualDays] = $this->splitAttendanceDays(
                (string) $sheet->getCell(self::COL_ATTENDANCE_DAYS.$r)->getCalculatedValue()
            );

            $rows[] = [
                'device_employee_code' => $employeeId,
                'device_name' => $name,
                'device_department' => $this->nullableString($sheet, self::COL_DEPARTMENT.$r),
                'work_duration_standard_minutes' => $this->readMinutes($sheet, self::COL_WORK_STANDARD.$r),
                'work_duration_actual_minutes' => $this->readMinutes($sheet, self::COL_WORK_ACTUAL.$r),
                'late_times' => $this->readInt($sheet, self::COL_LATE_TIMES.$r),
                'late_minutes' => $this->readMinutes($sheet, self::COL_LATE_MINUTES.$r),
                'leave_early_times' => $this->readInt($sheet, self::COL_LEAVE_EARLY_TIMES.$r),
                'leave_early_minutes' => $this->readMinutes($sheet, self::COL_LEAVE_EARLY_MINUTES.$r),
                'overtime_normal_minutes' => $this->readMinutes($sheet, self::COL_OT_NORMAL.$r),
                'overtime_special_minutes' => $this->readMinutes($sheet, self::COL_OT_SPECIAL.$r),
                'lack_times' => $this->readInt($sheet, self::COL_LACK_TIMES.$r),
                'lack_minutes' => $this->readMinutes($sheet, self::COL_LACK_MINUTES.$r),
                'attendance_days_standard' => $standardDays,
                'attendance_days_actual' => $actualDays,
                'absent_days' => $this->readInt($sheet, self::COL_ABSENT_DAYS.$r),
                'remarks' => $this->nullableString($sheet, self::COL_REMARKS.$r),
            ];
        }

        return $rows;
    }

    /**
     * A cell's raw numeric value is ambiguous on its own: HikVision sometimes
     * formats long durations as "[h]:mm"-style time (stored internally as a
     * fraction of a day, e.g. 8.0 for "192:00") and sometimes as a plain
     * number already in minutes (e.g. 11040). The cell's number format is
     * the only reliable signal for which one applies.
     */
    private function readMinutes(Worksheet $sheet, string $coordinate): int
    {
        $cell = $sheet->getCell($coordinate);
        $value = $cell->getCalculatedValue();

        if (! is_numeric($value)) {
            return 0;
        }

        $format = strtolower($cell->getStyle()->getNumberFormat()->getFormatCode());
        $isDurationFormat = str_contains($format, ':') || str_contains($format, '[h]');

        return $isDurationFormat ? (int) round(((float) $value) * 1440) : (int) round((float) $value);
    }

    private function readInt(Worksheet $sheet, string $coordinate): int
    {
        $value = $sheet->getCell($coordinate)->getCalculatedValue();

        return is_numeric($value) ? (int) round((float) $value) : 0;
    }

    private function nullableString(Worksheet $sheet, string $coordinate): ?string
    {
        $value = trim((string) $sheet->getCell($coordinate)->getCalculatedValue());

        return $value === '' ? null : $value;
    }

    /** @return array{0: int, 1: int} [standard, actual] */
    private function splitAttendanceDays(string $value): array
    {
        if (! str_contains($value, '/')) {
            return [0, 0];
        }

        [$standard, $actual] = array_pad(array_map('trim', explode('/', $value, 2)), 2, '0');

        return [is_numeric($standard) ? (int) $standard : 0, is_numeric($actual) ? (int) $actual : 0];
    }

    /** @return array{start: ?Carbon, end: ?Carbon} */
    private function readMadeDateRange(Worksheet $sheet): array
    {
        foreach ($sheet->getRowIterator(1, 5) as $row) {
            foreach ($row->getCellIterator() as $cell) {
                $text = (string) $cell->getValue();

                if (preg_match('/Made Date:\s*(\d{4}\/\d{2}\/\d{2})\s*-\s*(\d{4}\/\d{2}\/\d{2})/', $text, $m)) {
                    return [
                        'start' => Carbon::createFromFormat('Y/m/d', $m[1])->startOfDay(),
                        'end' => Carbon::createFromFormat('Y/m/d', $m[2])->startOfDay(),
                    ];
                }
            }
        }

        return ['start' => null, 'end' => null];
    }
}
