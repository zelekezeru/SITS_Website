<?php

namespace App\Http\Controllers\Bookstore;

use App\Http\Controllers\Controller;
use App\Models\StudyMode;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

/**
 * Study modes are a lookup table rather than an enum precisely so an admin can
 * add one (weekend, online, …) without a deploy.
 */
class StudyModeController extends Controller
{
    public function index(): Response
    {
        return Inertia::render('Bookstore/StudyModes/Index', [
            'studyModes' => StudyMode::ordered()->withCount('bookTitles')->get(),
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        StudyMode::create($this->validated($request));

        return back()->with('success', 'Study mode added.');
    }

    public function update(Request $request, StudyMode $studyMode): RedirectResponse
    {
        $studyMode->update($this->validated($request, $studyMode));

        return back()->with('success', 'Study mode updated.');
    }

    public function destroy(StudyMode $studyMode): RedirectResponse
    {
        if ($studyMode->bookTitles()->exists()) {
            return back()->with('error', 'Books are still classified under this mode. Deactivate it instead.');
        }

        $studyMode->delete();

        return back()->with('success', 'Study mode removed.');
    }

    /** @return array<string, mixed> */
    protected function validated(Request $request, ?StudyMode $studyMode = null): array
    {
        return $request->validate([
            'name'        => 'required|string|max:255',
            'code'        => 'required|string|max:20|unique:study_modes,code'.($studyMode ? ",{$studyMode->id}" : ''),
            'description' => 'nullable|string',
            'is_active'   => 'boolean',
            'sort_order'  => 'nullable|integer|min:0',
        ]);
    }
}
