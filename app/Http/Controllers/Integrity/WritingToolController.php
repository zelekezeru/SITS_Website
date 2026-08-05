<?php

namespace App\Http\Controllers\Integrity;

use App\Enums\WritingReportType;
use App\Http\Controllers\Controller;
use App\Jobs\RunWritingTool;
use App\Models\IntegrityDocument;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;

class WritingToolController extends Controller
{
    public function run(Request $request, IntegrityDocument $document, string $type)
    {
        Gate::authorize('view', $document);

        $writingType = WritingReportType::tryFrom($type);
        if (! $writingType) {
            abort(404, "Unknown writing tool: {$type}");
        }

        RunWritingTool::dispatch($document, $writingType, (string) $request->input('notes', ''));

        return back()->with('success', ucfirst($type).' queued.');
    }
}
