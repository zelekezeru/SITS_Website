<?php

namespace App\Http\Controllers\Library;

use App\Http\Controllers\Controller;

use App\Models\Hold;
use Illuminate\Http\Request;

class HoldController extends Controller
{
    public function store(Request $request)
    {
        $data = $request->validate([
            'book_id' => 'required|exists:books,id',
            'campus_id' => 'required|exists:campuses,id',
        ]);

        $alreadyHolding = $request->user()->holds()
            ->where('book_id', $data['book_id'])
            ->whereIn('status', ['waiting', 'ready'])
            ->exists();
        if ($alreadyHolding) {
            return back()->with('error', 'You already have an active hold on this title.');
        }

        $maxHolds = (int) config('library.max_active_holds', 5);
        $activeHoldsCount = $request->user()->holds()->whereIn('status', ['waiting', 'ready'])->count();
        if ($activeHoldsCount >= $maxHolds) {
            return back()->with('error', "You have reached the maximum of {$maxHolds} active holds.");
        }

        $hold = Hold::create([
            'book_id' => $data['book_id'],
            'user_id' => $request->user()->id,
            'campus_id' => $data['campus_id'],
            'placed_at' => now(),
            'status' => 'waiting',
        ]);

        // Phase 7 auto-request hook: If there's an available copy at another campus, request it.
        $availableLocally = \App\Models\BookCopy::where('book_id', $data['book_id'])
            ->where('status', 'available')
            ->whereHas('shelfBox.row.floor', fn($q) => $q->where('campus_id', $data['campus_id']))
            ->exists();

        if (!$availableLocally) {
            $availableRemote = \App\Models\BookCopy::where('book_id', $data['book_id'])
                ->where('status', 'available')
                ->first();
                
            if ($availableRemote) {
                app(\App\Services\TransferService::class)->request(
                    $availableRemote, 
                    $data['campus_id'], 
                    $request->user(), 
                    'Auto-requested for Hold #' . $hold->id,
                    $hold
                );
            }
        }

        return back()->with('success', 'Hold placed successfully. We will notify you when it is ready.');
    }

    public function cancel(Request $request, Hold $hold)
    {
        abort_if($hold->user_id !== $request->user()->id && !$request->user()->can('manage_holds'), 403);

        $hold->update(['status' => 'cancelled']);

        return back()->with('success', 'Hold cancelled.');
    }
}
