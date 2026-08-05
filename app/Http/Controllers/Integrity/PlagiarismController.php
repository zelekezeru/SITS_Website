<?php

namespace App\Http\Controllers\Integrity;

use App\Http\Controllers\Controller;
use App\Models\IntegrityDocument;
use App\Services\Integrity\IntegrityQuota;
use App\Services\Integrity\Plagiarism\CorpusMatcher;
use App\Services\Integrity\Plagiarism\WebMatcher;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;

class PlagiarismController extends Controller
{
    public function run(Request $request, IntegrityDocument $document, CorpusMatcher $corpusMatcher, WebMatcher $webMatcher, IntegrityQuota $quota)
    {
        Gate::authorize('update', $document);

        $type = $request->input('type', 'corpus');

        if ($type === 'web') {
            $weight = (int) config('integrity.web_check_quota_weight', 5);
            if (! $quota->canConsume($request->user(), $weight)) {
                abort(429, "You've reached your daily analysis quota ({$quota->limit()}/day). The web-source check counts {$weight}x — please try again tomorrow.");
            }

            $webMatcher->checkAndPersist($document);

            return back()->with('success', 'Web-source check complete.');
        }

        $corpusMatcher->matchAndPersist($document, includeSelf: $request->boolean('include_self'));

        return back()->with('success', 'Plagiarism check complete.');
    }
}
