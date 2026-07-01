<?php

namespace App\Http\Controllers\Library;

use App\Http\Controllers\Controller;

use App\Models\BookCopy;
use App\Models\Campus;
use App\Models\Loan;
use App\Models\User;
use App\Services\CirculationService;
use Illuminate\Http\Request;
use Inertia\Inertia;

class CirculationController extends Controller
{
    protected CirculationService $circulation;

    public function __construct(CirculationService $circulation)
    {
        $this->circulation = $circulation;
    }

    public function desk()
    {
        return Inertia::render('Circulation/Desk', [
            'currency' => config('library.currency_symbol'),
        ]);
    }

    public function returns()
    {
        return Inertia::render('Circulation/Returns', [
            'campuses' => Campus::orderBy('name')->get(['id','name','code']),
        ]);
    }

    public function lookupUser(Request $request)
    {
        $q = trim((string) $request->input('q'));

        if ($q === '') {
            return response()->json(['message' => 'Enter a name, email, or ID.'], 422);
        }

        $withBorrowerContext = fn () => User::with(['fines' => fn($f) => $f->where('status', 'open')])
            ->withCount(['loans as active_loans_count' => fn($l) => $l->where('status', 'active')]);

        // An exact email or numeric ID (e.g. a scanned patron QR) resolves immediately.
        $user = $withBorrowerContext()
            ->where(fn ($query) => $query->where('email', $q)
                ->when(ctype_digit($q), fn ($query) => $query->orWhere('id', $q)))
            ->first();

        if ($user) {
            return response()->json($user);
        }

        // Fall back to a partial name/email search for staff typing at the desk.
        $candidates = User::where('name', 'like', "%{$q}%")
            ->orWhere('email', 'like', "%{$q}%")
            ->orderBy('name')
            ->limit(8)
            ->get(['id', 'name', 'email']);

        if ($candidates->isEmpty()) {
            return response()->json(['message' => 'User not found'], 404);
        }

        if ($candidates->count() === 1) {
            return response()->json($withBorrowerContext()->find($candidates->first()->id));
        }

        return response()->json(['candidates' => $candidates]);
    }

    public function checkout(Request $request)
    {
        $data = $request->validate([
            'borrower_id' => 'required|exists:users,id',
            'copy_hash'   => 'required|string',
        ]);

        $copy = BookCopy::with('book')->where('tracking_hash', $data['copy_hash'])->firstOrFail();
        $borrower = User::findOrFail($data['borrower_id']);

        try {
            $loan = $this->circulation->checkout($copy, $borrower, $request->user());
            return response()->json([
                'message' => "Checked out {$copy->book->title}",
                'due_on'  => $loan->due_on->format('M j, Y'),
            ]);
        } catch (\Exception $e) {
            return response()->json(['message' => $e->getMessage()], 422);
        }
    }

    public function return(Request $request)
    {
        $data = $request->validate([
            'copy_hash'        => 'required|string',
            'return_campus_id' => 'required|exists:campuses,id',
        ]);

        $copy = BookCopy::with('book')->where('tracking_hash', $data['copy_hash'])->firstOrFail();

        try {
            $loan = $this->circulation->return($copy, $request->user(), $data['return_campus_id']);
            return response()->json([
                'message' => "Returned {$copy->book->title} " . ($loan->status === 'overdue_returned' ? '(Overdue)' : ''),
            ]);
        } catch (\Exception $e) {
            return response()->json(['message' => $e->getMessage()], 422);
        }
    }

    public function renew(Request $request, Loan $loan)
    {
        try {
            $loan = $this->circulation->renew($loan, $request->user());
            return back()->with('success', 'Loan renewed until ' . $loan->due_on->format('M j, Y'));
        } catch (\Exception $e) {
            return back()->with('error', $e->getMessage());
        }
    }
}
