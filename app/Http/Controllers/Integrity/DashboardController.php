<?php

namespace App\Http\Controllers\Integrity;

use App\Enums\Role;
use App\Http\Controllers\Controller;
use App\Models\CorpusFingerprint;
use App\Models\IntegrityDocument;
use App\Models\IntegrityReport;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Inertia\Inertia;

class DashboardController extends Controller
{
    public function index(Request $request)
    {
        Gate::authorize('access-integrity-suite');

        $user = $request->user();
        $isAdmin = in_array($user->primaryRole(), [Role::SUPER_ADMIN, Role::CAMPUS_ADMIN], true);

        $documents = $isAdmin ? IntegrityDocument::query() : IntegrityDocument::forInstructor($user);
        $reports = $isAdmin
            ? IntegrityReport::query()
            : IntegrityReport::whereHas('document', fn ($q) => $q->forInstructor($user));

        return Inertia::render('Integrity/Dashboard', [
            'stats' => [
                'analyzed_this_term' => (clone $documents)->count(),
                'flagged_awaiting_review' => (clone $reports)->where('flagged', true)->where('review_status', 'none')->count(),
                'avg_ai_probability' => (int) round((clone $reports)->whereNotNull('ai_probability')->avg('ai_probability') ?? 0),
                'corpus_size' => CorpusFingerprint::distinct('integrity_document_id')->count('integrity_document_id'),
            ],
            'recentDocuments' => (clone $documents)->with('report')->latest()->limit(10)->get(),
        ]);
    }

    public function history(Request $request)
    {
        Gate::authorize('access-integrity-suite');

        $user = $request->user();
        $isAdmin = in_array($user->primaryRole(), [Role::SUPER_ADMIN, Role::CAMPUS_ADMIN], true);

        $query = $isAdmin ? IntegrityDocument::query() : IntegrityDocument::forInstructor($user);

        $query->with('report')
            ->when($request->filled('course_id'), fn ($q) => $q->where('course_id', $request->input('course_id')))
            ->when($request->filled('student_id'), fn ($q) => $q->where('student_id', $request->input('student_id')))
            ->when($request->filled('status'), fn ($q) => $q->where('status', $request->input('status')))
            ->when($request->boolean('flagged'), fn ($q) => $q->whereHas('report', fn ($r) => $r->where('flagged', true)))
            ->when($request->filled('from'), fn ($q) => $q->whereDate('created_at', '>=', $request->input('from')))
            ->when($request->filled('to'), fn ($q) => $q->whereDate('created_at', '<=', $request->input('to')));

        return Inertia::render('Integrity/History', [
            'documents' => $query->latest()->paginate(20)->withQueryString(),
            'filters' => $request->only(['course_id', 'student_id', 'status', 'flagged', 'from', 'to']),
        ]);
    }
}
