<?php

namespace App\Http\Controllers\Library;

use App\Http\Controllers\Controller;

use App\Models\BookCopy;
use App\Models\Campus;
use App\Models\Transfer;
use App\Services\TransferService;
use Illuminate\Http\Request;
use Inertia\Inertia;

class TransferController extends Controller
{
    protected TransferService $transfers;

    public function __construct(TransferService $transfers)
    {
        $this->transfers = $transfers;
    }

    public function index(Request $request)
    {
        $campusId = $request->user()->current_campus_id;

        $incoming = Transfer::with(['copy.book', 'fromCampus', 'toCampus', 'requester'])
            ->where('to_campus_id', $campusId)
            ->whereIn('status', ['requested', 'approved', 'in_transit'])
            ->orderBy('created_at', 'desc')
            ->get();

        $outgoing = Transfer::with(['copy.book', 'fromCampus', 'toCampus', 'requester'])
            ->where('from_campus_id', $campusId)
            ->whereIn('status', ['requested', 'approved', 'in_transit'])
            ->orderBy('created_at', 'desc')
            ->get();

        $history = Transfer::with(['copy.book', 'fromCampus', 'toCampus'])
            ->where(function($q) use ($campusId) {
                $q->where('from_campus_id', $campusId)->orWhere('to_campus_id', $campusId);
            })
            ->whereNotIn('status', ['requested', 'approved', 'in_transit'])
            ->orderBy('updated_at', 'desc')
            ->take(50)
            ->get();

        return Inertia::render('Library/Transfers/Index', [
            'incoming' => $incoming,
            'outgoing' => $outgoing,
            'history'  => $history,
        ]);
    }

    public function show(Transfer $transfer)
    {
        $transfer->load(['copy.book', 'fromCampus', 'toCampus', 'requester', 'approver', 'dispatcher', 'receiver']);
        
        return Inertia::render('Library/Transfers/Show', [
            'transfer' => $transfer,
        ]);
    }

    public function create(Request $request)
    {
        return Inertia::render('Library/Transfers/Create', [
            'campuses' => Campus::orderBy('name')->get(['id', 'name']),
            'copy_id' => $request->query('copy_id'),
        ]);
    }

    public function store(Request $request)
    {
        $data = $request->validate([
            'copy_hash' => 'required|string|exists:book_copies,tracking_hash',
            'to_campus_id' => 'required|exists:campuses,id',
            'reason' => 'nullable|string|max:500',
        ]);

        $copy = BookCopy::where('tracking_hash', $data['copy_hash'])->firstOrFail();

        try {
            $transfer = $this->transfers->request($copy, $data['to_campus_id'], $request->user(), $data['reason']);
            return redirect()->route('transfers.show', $transfer)->with('success', 'Transfer requested.');
        } catch (\Exception $e) {
            return back()->with('error', $e->getMessage());
        }
    }

    public function approve(Request $request, Transfer $transfer)
    {
        try {
            $this->transfers->approve($transfer, $request->user());
            return back()->with('success', 'Transfer approved.');
        } catch (\Exception $e) {
            return back()->with('error', $e->getMessage());
        }
    }

    public function reject(Request $request, Transfer $transfer)
    {
        $data = $request->validate(['note' => 'required|string']);
        try {
            $this->transfers->reject($transfer, $request->user(), $data['note']);
            return back()->with('success', 'Transfer rejected.');
        } catch (\Exception $e) {
            return back()->with('error', $e->getMessage());
        }
    }

    public function dispatch(Request $request, Transfer $transfer)
    {
        $data = $request->validate(['courier_ref' => 'nullable|string']);
        try {
            $this->transfers->dispatch($transfer, $request->user(), $data['courier_ref'] ?? null);
            return back()->with('success', 'Transfer dispatched. Copy is now in transit.');
        } catch (\Exception $e) {
            return back()->with('error', $e->getMessage());
        }
    }

    public function receive(Request $request, Transfer $transfer)
    {
        try {
            $this->transfers->receive($transfer, $request->user());
            return back()->with('success', 'Transfer received. Please place the copy on a shelf using Scan & Place.');
        } catch (\Exception $e) {
            return back()->with('error', $e->getMessage());
        }
    }

    public function returnToOrigin(Request $request, Transfer $transfer)
    {
        $data = $request->validate(['note' => 'required|string']);
        try {
            $this->transfers->returnToOrigin($transfer, $request->user(), $data['note']);
            return back()->with('success', 'Transfer returned to origin.');
        } catch (\Exception $e) {
            return back()->with('error', $e->getMessage());
        }
    }

    public function markLost(Request $request, Transfer $transfer)
    {
        $data = $request->validate(['note' => 'required|string']);
        try {
            $this->transfers->markLost($transfer, $request->user(), $data['note']);
            return back()->with('success', 'Transfer marked as lost.');
        } catch (\Exception $e) {
            return back()->with('error', $e->getMessage());
        }
    }
}
