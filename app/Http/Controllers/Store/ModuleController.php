<?php

namespace App\Http\Controllers\Store;

use App\Http\Controllers\Controller;
use App\Support\PortalContext;
use App\Support\StoreNavigation;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

/**
 * Resolves any store module page from the current route name against the single
 * navigation tree, so every sidebar link has a real, permission-gated page
 * behind it — the same contract Admin\ModuleController provides for the
 * President's modules.
 *
 * Phase 0 renders each module's metadata (purpose + planned capabilities). As
 * phases 1–5 land, each route peels off into its own controller and this one is
 * left holding only what's still to come.
 */
class ModuleController extends Controller
{
    public function __invoke(Request $request): Response
    {
        $module = StoreNavigation::module($request->route()->getName());

        abort_if($module === null, 404);

        return Inertia::render('Store/Module', [
            'module' => $module,
            // The President reaches these pages from the admin sidebar; keep the
            // shell they arrived in rather than swapping it for the store's.
            'nav' => PortalContext::for($request->user())['nav'],
        ]);
    }
}
