<?php

namespace App\Http\Controllers\Library;

use App\Http\Controllers\Controller;

use App\Models\Loan;
use App\Services\CirculationService;
use App\Services\LoanPolicy;
use Illuminate\Http\Request;
use Inertia\Inertia;

class MyLoansController extends Controller
{
    public function index(Request $request)
    {
        $loans = $request->user()->loans()
            ->with(['copy.book'])
            ->orderByRaw("CASE WHEN status = 'active' THEN 0 ELSE 1 END")
            ->orderBy('due_on', 'asc')
            ->paginate(15);

        return Inertia::render('Library/Circulation/MyLoans', [
            'loans'       => $loans,
            'maxRenewals' => LoanPolicy::for($request->user())->maxRenewals,
        ]);
    }

    /** Patron self-service renewal of their own active loan. */
    public function renew(Request $request, Loan $loan, CirculationService $circulation)
    {
        abort_unless($loan->user_id === $request->user()->id, 403);

        $circulation->renew($loan, $request->user());

        return back()->with('success', 'Your loan has been renewed.');
    }
}
