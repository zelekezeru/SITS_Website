<?php

namespace App\Http\Controllers\Integrity;

use App\Enums\IntegrityDocumentSource;
use App\Enums\IntegrityDocumentStatus;
use App\Http\Controllers\Controller;
use App\Jobs\RunIntegrityAnalysis;
use App\Models\IntegrityDocument;
use App\Services\Integrity\IntegrityQuota;
use App\Services\Integrity\TextExtractor;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Inertia\Inertia;

class DocumentController extends Controller
{
    public function store(Request $request, TextExtractor $extractor, IntegrityQuota $quota)
    {
        Gate::authorize('create', IntegrityDocument::class);

        $validated = $request->validate([
            'title' => 'required|string|max:255',
            'text' => 'nullable|string',
            'file' => 'nullable|file|mimes:docx,pdf,txt|max:20480',
            'student_id' => 'nullable|exists:users,id',
            'course_id' => 'nullable|exists:courses,id',
        ]);

        if (blank($validated['text'] ?? null) && ! $request->hasFile('file')) {
            return back()->withErrors(['text' => 'Paste some text or upload a file (docx, pdf, or txt).']);
        }

        if (! $quota->canConsume($request->user())) {
            abort(429, "You've reached your daily analysis quota ({$quota->limit()}/day). Please try again tomorrow.");
        }

        if ($request->hasFile('file')) {
            $file = $request->file('file');
            $extraction = $extractor->extractFromPath($file->getRealPath(), $file->getClientOriginalExtension());
            $source = IntegrityDocumentSource::UPLOAD;
            $originalFilename = $file->getClientOriginalName();
            $mimeType = $file->getClientMimeType();
        } else {
            $extraction = $extractor->extractFromPastedText($validated['text']);
            $source = IntegrityDocumentSource::PASTE;
            $originalFilename = null;
            $mimeType = null;
        }

        $document = IntegrityDocument::create([
            'instructor_id' => $request->user()->id,
            'student_id' => $validated['student_id'] ?? null,
            'course_id' => $validated['course_id'] ?? null,
            'title' => $validated['title'],
            'original_filename' => $originalFilename,
            'mime_type' => $mimeType,
            'source' => $source,
            'word_count' => $extraction['word_count'],
            'extracted_text' => $extraction['text'],
            'status' => $extraction['success'] ? IntegrityDocumentStatus::PENDING : IntegrityDocumentStatus::FAILED,
            'failure_reason' => $extraction['failure_reason'] ?? null,
        ]);

        if ($extraction['success']) {
            RunIntegrityAnalysis::dispatch($document);
        }

        return redirect()->route('integrity.documents.show', $document)
            ->with('success', $extraction['success'] ? 'Analysis started.' : 'Could not extract text from that file.');
    }

    public function show(IntegrityDocument $document)
    {
        Gate::authorize('view', $document);

        $document->load(['report', 'plagiarismReports', 'writingReports', 'instructor', 'student', 'course']);

        return Inertia::render('Integrity/Report', [
            'document' => $document,
        ]);
    }

    public function reanalyze(Request $request, IntegrityDocument $document, IntegrityQuota $quota)
    {
        Gate::authorize('update', $document);

        if (! $quota->canConsume($request->user())) {
            abort(429, "You've reached your daily analysis quota ({$quota->limit()}/day). Please try again tomorrow.");
        }

        RunIntegrityAnalysis::dispatch($document);

        return back()->with('success', 'Reanalysis queued.');
    }
}
