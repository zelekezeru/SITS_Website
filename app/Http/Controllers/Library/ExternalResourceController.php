<?php

namespace App\Http\Controllers\Library;

use App\Http\Controllers\Controller;

use App\Models\ExternalResource;
use App\Models\ExternalResourceClick;
use Illuminate\Http\Request;
use Inertia\Inertia;
use SimpleSoftwareIO\QrCode\Facades\QrCode;

class ExternalResourceController extends Controller
{
    public function index(Request $request)
    {
        return Inertia::render('Library/Resources/Index', [
            'resources' => ExternalResource::visibleTo($request->user())->orderBy('sort_order')->orderBy('name')->get()->groupBy('category'),
        ]);
    }

    public function go(Request $request, ExternalResource $resource)
    {
        abort_unless($resource->isVisibleTo($request->user()), 403);

        ExternalResourceClick::create([
            'external_resource_id' => $resource->id,
            'user_id' => $request->user()?->id,
            'ip' => $request->ip(),
            'clicked_at' => now(),
        ]);

        return redirect()->away($resource->url);
    }

    public function qr(ExternalResource $resource)
    {
        $url = route('resources.go', $resource->id);
        $svg = QrCode::format('svg')->size(300)->errorCorrection('M')->generate($url);

        return response($svg, 200, ['Content-Type' => 'image/svg+xml']);
    }
}
