<?php

namespace App\Http\Controllers\Bookstore;

use App\Http\Controllers\Controller;
use App\Models\Campus;
use App\Models\Center;
use App\Models\User;
use App\Services\Bookstore\ReturnService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class CenterController extends Controller
{
    public function index(Request $request): Response
    {
        $centers = Center::query()
            ->with(['campus:id,name,name_en', 'coordinator:id,name'])
            ->withCount('bookRequests')
            ->when($request->input('search'), fn ($q, $term) => $q->where(fn ($sub) => $sub
                ->where('name', 'like', "%{$term}%")
                ->orWhere('code', 'like', "%{$term}%")
                ->orWhere('coordinator_name', 'like', "%{$term}%")))
            ->orderBy('name')
            ->paginate(20)
            ->withQueryString();

        return Inertia::render('Bookstore/Centers/Index', [
            'centers' => $centers,
            'filters' => $request->only('search'),
            'options' => $this->formOptions(),
        ]);
    }

    /**
     * The centre statement: what it was issued, what came back, what is still
     * outstanding — the reconciliation the paper form works out by hand.
     */
    public function show(Center $center, ReturnService $returns): Response
    {
        $center->load(['campus:id,name,name_en', 'coordinator:id,name']);

        $outstanding = $returns->outstandingForCenter($center);

        return Inertia::render('Bookstore/Centers/Show', [
            'center'      => $center,
            'outstanding' => $outstanding,
            'totals'      => [
                'issued'      => (int) $outstanding->sum('issued'),
                'returned'    => (int) $outstanding->sum('returned'),
                'outstanding' => (int) $outstanding->sum('outstanding'),
                'value'       => round($outstanding->sum(fn ($row) => $row->outstanding * (float) $row->unit_price), 2),
            ],
            'requests' => $center->bookRequests()
                ->with('requester:id,name')
                ->orderByDesc('created_at')
                ->limit(20)
                ->get(),
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        Center::create($this->validated($request));

        return back()->with('success', 'Distribution centre created.');
    }

    public function update(Request $request, Center $center): RedirectResponse
    {
        $center->update($this->validated($request, $center));

        return back()->with('success', 'Distribution centre updated.');
    }

    public function destroy(Center $center): RedirectResponse
    {
        $center->delete();

        return redirect()->route('bookstore.centers.index')->with('success', 'Distribution centre archived.');
    }

    /** @return array<string, mixed> */
    protected function validated(Request $request, ?Center $center = null): array
    {
        return $request->validate([
            'name'                => 'required|string|max:255',
            'code'                => 'required|string|max:30|unique:centers,code'.($center ? ",{$center->id}" : ''),
            'city'                => 'nullable|string|max:255',
            'region'              => 'nullable|string|max:255',
            'coordinator_name'    => 'nullable|string|max:255',
            'coordinator_phone'   => 'nullable|string|max:30',
            'coordinator_user_id' => 'nullable|exists:users,id',
            'student_count'       => 'required|integer|min:0',
            'campus_id'           => 'nullable|exists:campuses,id',
            'is_active'           => 'boolean',
            'notes'               => 'nullable|string',
        ]);
    }

    /** @return array<string, mixed> */
    protected function formOptions(): array
    {
        return [
            'campuses'     => Campus::orderBy('name')->get(['id', 'name', 'name_en']),
            'coordinators' => User::orderBy('name')->limit(200)->get(['id', 'name']),
        ];
    }
}
