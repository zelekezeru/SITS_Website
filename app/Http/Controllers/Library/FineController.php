<?php

namespace App\Http\Controllers\Library;

use App\Http\Controllers\Controller;

use App\Models\Fine;
use App\Models\User;
use App\Notifications\FineIssued;
use App\Services\Payments\PaymentService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Inertia\Inertia;

class FineController extends Controller
{
    public function index(Request $request)
    {
        $status = $request->string('status')->toString() ?: 'open';
        $search = $request->string('q')->trim()->toString();

        $fines = Fine::query()
            ->with(['user:id,name,email', 'loan.copy.book'])
            ->when($status !== 'all', fn ($q) => $q->where('status', $status))
            ->when($search, fn ($q) => $q->whereHas('user', fn ($u) =>
                $u->where('name', 'like', "%{$search}%")->orWhere('email', 'like', "%{$search}%")
            ))
            ->latest()
            ->paginate(20)
            ->withQueryString();

        $totals = [
            'outstanding' => round((float) Fine::outstanding()->sum(DB::raw('amount - paid_amount')), 2),
            'collected'   => round((float) Fine::sum('paid_amount'), 2),
            'open_count'  => Fine::where('status', 'open')->count(),
        ];

        return Inertia::render('Library/Fines/Index', [
            'fines'    => $fines,
            'filters'  => ['status' => $status, 'q' => $search],
            'totals'   => $totals,
            'currency' => config('library.currency_symbol'),
        ]);
    }

    /** Issue a manual fine (lost / damaged item, etc.). */
    public function store(Request $request)
    {
        $data = $request->validate([
            'user_id' => ['required', 'exists:users,id'],
            'reason'  => ['required', 'in:lost,damaged,overdue'],
            'amount'  => ['required', 'numeric', 'min:0.01'],
            'note'    => ['nullable', 'string', 'max:500'],
        ]);

        $fine = Fine::create([
            'user_id'     => $data['user_id'],
            'reason'      => $data['reason'],
            'amount'      => $data['amount'],
            'paid_amount' => 0,
            'status'      => 'open',
            'note'        => $data['note'] ?? null,
        ]);

        User::find($data['user_id'])?->notify(new FineIssued($fine));

        return back()->with('success', 'Fine issued and the patron has been notified.');
    }

    /** Record a payment taken at the desk. */
    public function collect(Request $request, Fine $fine, PaymentService $payments)
    {
        $request->user()->can('collect_fine') ?: abort(403);

        $data = $request->validate([
            'amount' => ['required', 'numeric', 'min:0.01'],
            'method' => ['nullable', 'in:cash,chapa,telebirr,card'],
        ]);

        $payments->recordManual($fine, (float) $data['amount'], $request->user(), $data['method'] ?? 'cash');

        return back()->with('success', 'Payment recorded.');
    }

    /** Forgive an outstanding fine. */
    public function waive(Request $request, Fine $fine)
    {
        abort_unless($request->user()->can('waive_fine'), 403);

        $data = $request->validate(['note' => ['nullable', 'string', 'max:500']]);

        $fine->update([
            'status' => 'waived',
            'note'   => trim(($fine->note ? $fine->note . ' | ' : '') . 'Waived: ' . ($data['note'] ?? 'no reason given')),
        ]);

        return back()->with('success', 'Fine waived.');
    }
}
