<?php

namespace App\Services\Payroll;

use App\Models\PayrollPeriod;
use App\Support\PayrollSheetPresenter;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Style\Alignment;
use PhpOffice\PhpSpreadsheet\Style\Fill;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;
use Symfony\Component\HttpFoundation\StreamedResponse;

/**
 * Streams the payroll period sheet as an .xlsx workbook — the same columns as
 * the on-screen summary and PDF, with a styled header band and a totals row.
 */
class PayrollSheetExport
{
    /** @param array<int, array<string, mixed>> $rows */
    public function download(PayrollPeriod $period, array $rows): StreamedResponse
    {
        $spreadsheet = new Spreadsheet();
        $sheet = $spreadsheet->getActiveSheet();
        $sheet->setTitle('Payroll');

        $headers = array_merge(
            ['#', 'Employee', 'Grade', 'Campus', 'Working Days'],
            array_values(PayrollSheetPresenter::COLUMNS)
        );
        $numericKeys = array_keys(PayrollSheetPresenter::COLUMNS);

        // Title + header.
        $lastCol = \PhpOffice\PhpSpreadsheet\Cell\Coordinate::stringFromColumnIndex(count($headers));
        $sheet->setCellValue('A1', "SITS Seminary — Payroll: {$period->name}");
        $sheet->mergeCells("A1:{$lastCol}1");
        $sheet->getStyle('A1')->getFont()->setBold(true)->setSize(13);

        $headerRow = 3;
        $sheet->fromArray($headers, null, "A{$headerRow}");
        $sheet->getStyle("A{$headerRow}:{$lastCol}{$headerRow}")->applyFromArray([
            'font' => ['bold' => true, 'color' => ['rgb' => 'FFFFFF']],
            'fill' => ['fillType' => Fill::FILL_SOLID, 'startColor' => ['rgb' => '1E3A8A']],
            'alignment' => ['horizontal' => Alignment::HORIZONTAL_CENTER, 'wrapText' => true],
        ]);

        // Data rows.
        $r = $headerRow + 1;
        foreach ($rows as $row) {
            $line = [$row['no'], $row['name'], $row['grade'], $row['campus'], $row['working_days']];
            foreach ($numericKeys as $key) {
                $line[] = $row[$key];
            }
            $sheet->fromArray($line, null, "A{$r}");
            $r++;
        }

        // Totals row.
        $totals = PayrollSheetPresenter::totals($rows);
        $totalLine = ['', 'TOTAL', '', '', ''];
        foreach ($numericKeys as $key) {
            $totalLine[] = $totals[$key];
        }
        $sheet->fromArray($totalLine, null, "A{$r}");
        $sheet->getStyle("A{$r}:{$lastCol}{$r}")->applyFromArray([
            'font' => ['bold' => true],
            'fill' => ['fillType' => Fill::FILL_SOLID, 'startColor' => ['rgb' => 'E2E8F0']],
        ]);

        // Money number format on numeric columns (col 6 onward).
        $firstNumCol = \PhpOffice\PhpSpreadsheet\Cell\Coordinate::stringFromColumnIndex(6);
        $sheet->getStyle("{$firstNumCol}{$headerRow}:{$lastCol}{$r}")
            ->getNumberFormat()->setFormatCode('#,##0.00');

        foreach (range(1, count($headers)) as $col) {
            $sheet->getColumnDimensionByColumn($col)->setAutoSize(true);
        }

        $filename = 'payroll_'.str_replace(' ', '_', $period->name).'.xlsx';

        return response()->streamDownload(function () use ($spreadsheet) {
            (new Xlsx($spreadsheet))->save('php://output');
        }, $filename, [
            'Content-Type' => 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
        ]);
    }
}
