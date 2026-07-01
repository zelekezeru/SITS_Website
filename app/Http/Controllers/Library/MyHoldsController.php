<?php

namespace App\Http\Controllers\Library;

use App\Http\Controllers\Controller;

use Illuminate\Http\Request;
use Inertia\Inertia;

class MyHoldsController extends Controller
{
    public function index(Request $request)
    {
        $holds = $request->user()->holds()
            ->with(['book', 'campus'])
            ->orderByRaw("CASE WHEN status IN ('waiting', 'ready') THEN 0 ELSE 1 END")
            ->orderBy('placed_at', 'desc')
            ->paginate(15);

        return Inertia::render('Library/Circulation/MyHolds', [
            'holds' => $holds,
        ]);
    }
}
