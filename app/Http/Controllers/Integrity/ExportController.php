<?php

namespace App\Http\Controllers\Integrity;

use App\Http\Controllers\Controller;
use App\Models\IntegrityAuditLog;
use App\Models\IntegrityDocument;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Str;

class ExportController extends Controller
{
    public function pdf(Request $request, IntegrityDocument $document)
    {
        Gate::authorize('view', $document);

        $document->loadMissing(['instructor', 'student', 'course', 'report.reviewer']);
        $report = $document->report;

        if (! $report) {
            abort(422, 'No report available to export yet.');
        }

        $plagiarismReport = $document->plagiarismReports()->latest('id')->first();

        $studentSlug = $document->student ? Str::slug($document->student->name) : 'unlinked-student';
        $filename = "integrity-report-{$studentSlug}-".now()->format('Y-m-d').'.pdf';

        IntegrityAuditLog::record($report, $request->user(), 'exported', ['filename' => $filename]);

        return Pdf::loadView('integrity.report-pdf', [
            'document' => $document,
            'report' => $report,
            'plagiarismReport' => $plagiarismReport,
        ])->setPaper('a4', 'portrait')->download($filename);
    }
}
