<?php

namespace App\Http\Controllers\Library;

use App\Http\Controllers\Controller;

use App\Exports\ReportExport;
use App\Services\ReportService;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Maatwebsite\Excel\Facades\Excel;

class ReportController extends Controller
{
    public function __construct(protected ReportService $reports) {}

    public function index(Request $request)
    {
        $report = $request->get('report', 'circulation');
        if (! in_array($report, ReportService::TYPES, true)) {
            $report = 'circulation';
        }

        $from = $request->date('from') ?? today()->subDays(30);
        $to   = $request->date('to') ?? today();

        return Inertia::render('Reports/Index', [
            'report'   => $report,
            'from'     => $from->format('Y-m-d'),
            'to'       => $to->format('Y-m-d'),
            'data'     => $this->reports->build($report, $from, $to),
            'currency' => config('library.currency_symbol'),
            'reports'  => ReportService::available(),
        ]);
    }

    public function generate(Request $request)
    {
        $request->validate([
            'report_type' => 'required|string|in:' . implode(',', ReportService::TYPES),
            'from'        => 'nullable|date',
            'to'          => 'nullable|date|after_or_equal:from',
        ]);

        $from = $request->date('from') ?? today()->subDays(30);
        $to   = $request->date('to') ?? today();

        return response()->json($this->reports->build($request->get('report_type'), $from, $to));
    }

    public function export(Request $request)
    {
        $request->validate([
            'report_type' => 'required|string|in:' . implode(',', ReportService::TYPES),
            'from'        => 'nullable|date',
            'to'          => 'nullable|date|after_or_equal:from',
            'format'      => 'required|string|in:xlsx,csv,pdf',
        ]);

        $report = $request->get('report_type');
        $from   = $request->date('from') ?? today()->subDays(30);
        $to     = $request->date('to') ?? today();
        $format = $request->get('format');

        $label = collect(ReportService::available())->firstWhere('key', $report)['label'] ?? ucfirst($report);
        $sections = $this->reports->sections($report, $from, $to);
        $filename = "report_{$report}_{$from->toDateString()}_{$to->toDateString()}.{$format}";

        return match ($format) {
            'xlsx' => Excel::download(
                new ReportExport($label, $sections, $from->toDateString(), $to->toDateString()),
                $filename
            ),
            'pdf' => Pdf::loadView('reports.pdf', [
                    'reportLabel' => $label,
                    'sections'    => $sections,
                    'from'        => $from->toDateString(),
                    'to'          => $to->toDateString(),
                ])->setPaper('a4', $report === 'overdue' || $report === 'campus' ? 'landscape' : 'portrait')
                ->download($filename),
            default => response()->streamDownload(function () use ($label, $sections, $from, $to) {
                $handle = fopen('php://output', 'w');
                fputcsv($handle, [$label]);
                fputcsv($handle, ['Period', "{$from->toDateString()} to {$to->toDateString()}"]);
                fputcsv($handle, []);

                foreach ($sections as $section) {
                    fputcsv($handle, [$section['title']]);
                    fputcsv($handle, $section['headers']);
                    foreach ($section['rows'] as $row) {
                        fputcsv($handle, array_values($row));
                    }
                    fputcsv($handle, []);
                }

                fclose($handle);
            }, $filename, ['Content-Type' => 'text/csv']),
        };
    }
}
