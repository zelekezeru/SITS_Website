<?php

namespace App\Services\Attendance;

use App\Enums\AttendanceImportStatus;
use App\Enums\AttendanceRowMatchStatus;
use App\Enums\AttendanceSource;
use App\Enums\AttendanceStatus;
use App\Models\AttendanceImport;
use App\Models\AttendanceRecord;
use App\Models\PayrollPeriod;
use App\Models\User;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use RuntimeException;

class AttendanceImportService
{
    public function __construct(
        private HikvisionAttendanceParser $parser,
        private EmployeeMatcher $matcher,
    ) {
    }

    public function import(UploadedFile $file, PayrollPeriod $period): AttendanceImport
    {
        $storedPath = $file->store('attendance-imports', 'local');
        $parsed = $this->parser->parse(Storage::disk('local')->path($storedPath));

        $periodStart = $period->start_date;
        $periodEnd = $period->end_date;

        $counts = ['matched' => 0, 'ambiguous' => 0, 'unmatched' => 0, 'excluded' => 0];
        $rowsData = [];

        foreach ($parsed['rows'] as $row) {
            $resolution = $this->matcher->resolve($row, $periodStart, $periodEnd);
            $rowsData[] = array_merge($row, $resolution);

            $counts[$resolution['match_status']->value] = ($counts[$resolution['match_status']->value] ?? 0) + 1;
            if ($resolution['is_excluded']) {
                $counts['excluded']++;
            }
        }

        $dateRangeWarning = $this->dateRangeMismatchWarning($parsed['period'], $period);

        return DB::transaction(function () use ($period, $file, $storedPath, $counts, $rowsData, $dateRangeWarning) {
            $import = AttendanceImport::create([
                'payroll_period_id' => $period->id,
                'original_filename' => $file->getClientOriginalName(),
                'file_path'         => $storedPath,
                'status'            => AttendanceImportStatus::PendingReview,
                'matched_count'     => $counts['matched'],
                'ambiguous_count'   => $counts['ambiguous'],
                'unmatched_count'   => $counts['unmatched'],
                'excluded_count'    => $counts['excluded'],
                'review_notes'      => $dateRangeWarning,
            ]);

            $import->rows()->createMany($rowsData);

            return $import;
        });
    }

    /** @return array{created: int, skipped: int} */
    public function approve(AttendanceImport $import, User $reviewer): array
    {
        if (! $import->isPendingReview()) {
            throw new RuntimeException('Only a pending-review import can be approved.');
        }

        if ($import->payrollPeriod->isLocked()) {
            throw new RuntimeException('Cannot approve attendance into a locked or paid payroll period.');
        }

        $created = 0;
        $skipped = 0;

        foreach ($import->rows as $row) {
            if ($row->is_excluded || $row->match_status !== AttendanceRowMatchStatus::Matched || ! $row->employee_id) {
                $skipped++;
                continue;
            }

            AttendanceRecord::updateOrCreate(
                ['employee_id' => $row->employee_id, 'payroll_period_id' => $import->payroll_period_id],
                [
                    'work_hours' => round($row->work_duration_actual_minutes / 60, 2),
                    'late_minutes' => $row->late_minutes,
                    'absent_days' => $row->absent_days,
                    'permitted_days' => $row->suggested_permitted_days,
                    'source' => AttendanceSource::ExcelImport,
                    'status' => AttendanceStatus::Verified,
                ]
            );

            $created++;
        }

        $import->update([
            'status' => AttendanceImportStatus::Approved,
            'reviewed_by' => $reviewer->id,
            'reviewed_at' => now(),
        ]);

        return ['created' => $created, 'skipped' => $skipped];
    }

    /** @param array{start: ?\Carbon\Carbon, end: ?\Carbon\Carbon} $filePeriod */
    private function dateRangeMismatchWarning(array $filePeriod, PayrollPeriod $period): ?string
    {
        if (! $filePeriod['start'] || ! $filePeriod['end']) {
            return null;
        }

        $overlaps = $filePeriod['start']->lte($period->end_date) && $filePeriod['end']->gte($period->start_date);

        if ($overlaps) {
            return null;
        }

        return sprintf(
            "Note: the uploaded file's date range (%s – %s) does not overlap the selected payroll period (%s – %s). Double-check you picked the right period.",
            $filePeriod['start']->format('Y-m-d'),
            $filePeriod['end']->format('Y-m-d'),
            $period->start_date->format('Y-m-d'),
            $period->end_date->format('Y-m-d'),
        );
    }

    public function reject(AttendanceImport $import, User $reviewer, ?string $notes): void
    {
        if (! $import->isPendingReview()) {
            throw new RuntimeException('Only a pending-review import can be rejected.');
        }

        $import->update([
            'status' => AttendanceImportStatus::Rejected,
            'reviewed_by' => $reviewer->id,
            'reviewed_at' => now(),
            'review_notes' => $notes,
        ]);
    }
}
