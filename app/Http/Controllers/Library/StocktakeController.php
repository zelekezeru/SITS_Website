<?php

namespace App\Http\Controllers\Library;

use App\Http\Controllers\Controller;

use App\Models\BookCopy;
use App\Models\Campus;
use App\Models\Stocktake;
use App\Models\StocktakeScan;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Inertia\Inertia;

class StocktakeController extends Controller
{
    public function index(Request $request)
    {
        $stocktakes = Stocktake::with(['campus', 'starter'])
            ->withCount('scans')
            ->orderByDesc('created_at')
            ->paginate(15);

        return Inertia::render('Stocktake/Index', [
            'stocktakes' => $stocktakes,
            'campuses'   => Campus::where('is_active', true)->get(['id', 'name', 'code']),
        ]);
    }

    public function store(Request $request)
    {
        $request->validate([
            'campus_id' => 'required|exists:campuses,id',
            'notes'     => 'nullable|string|max:500',
        ]);

        // Only one active stocktake per campus at a time
        abort_if(
            Stocktake::where('campus_id', $request->campus_id)->inProgress()->exists(),
            422,
            'A stocktake is already in progress for this campus.'
        );

        $stocktake = Stocktake::create([
            'campus_id'  => $request->campus_id,
            'started_by' => $request->user()->id,
            'status'     => 'in_progress',
            'started_at' => now(),
            'notes'      => $request->notes,
        ]);

        return redirect()->route('stocktakes.show', $stocktake)
            ->with('success', 'Stocktake started.');
    }

    public function show(Stocktake $stocktake)
    {
        $stocktake->load(['campus', 'starter']);
        $stocktake->loadCount('scans');

        // Get scans with book info
        $scans = $stocktake->scans()
            ->with(['bookCopy.book', 'bookCopy.shelfBox.row.floor', 'scanner'])
            ->orderByDesc('scanned_at')
            ->paginate(20);

        // Discrepancy count
        $mismatches = $stocktake->scans()->mismatched()->count();

        // Expected copies at this campus
        $expectedCount = BookCopy::where('status', '!=', 'lost')
            ->atCampus($stocktake->campus_id)
            ->count();

        // Copies not yet scanned
        $scannedCopyIds = $stocktake->scans()->pluck('book_copy_id');
        $unscannedCount = BookCopy::where('status', '!=', 'lost')
            ->atCampus($stocktake->campus_id)
            ->whereNotIn('id', $scannedCopyIds)
            ->count();

        return Inertia::render('Stocktake/Show', [
            'stocktake'     => $stocktake,
            'scans'         => $scans,
            'mismatches'    => $mismatches,
            'expectedCount' => $expectedCount,
            'unscannedCount'=> $unscannedCount,
            'progress'      => $expectedCount > 0
                ? round(($stocktake->scans_count / $expectedCount) * 100, 1)
                : 100,
        ]);
    }

    /**
     * Scan a copy during a stocktake (via barcode/QR hash).
     */
    public function scan(Request $request, Stocktake $stocktake)
    {
        abort_if($stocktake->status !== 'in_progress', 422, 'Stocktake is not active.');

        $request->validate([
            'identifier' => 'required|string', // barcode or tracking hash
        ]);

        $copy = BookCopy::where('barcode', $request->identifier)
            ->orWhere('tracking_hash', $request->identifier)
            ->first();

        if (! $copy) {
            return back()->with('error', 'No copy found with that barcode/QR code.');
        }

        // Check if already scanned
        if ($stocktake->scans()->where('book_copy_id', $copy->id)->exists()) {
            return back()->with('error', 'This copy has already been scanned in this stocktake.');
        }

        // Check if the copy is at the expected campus
        $copyCampusId = $copy->shelfBox?->row?->floor?->campus_id;
        $locationMatch = $copyCampusId === $stocktake->campus_id;

        $scan = StocktakeScan::create([
            'stocktake_id'   => $stocktake->id,
            'book_copy_id'   => $copy->id,
            'scanned_by'     => $request->user()->id,
            'location_match' => $locationMatch,
            'found_location' => ! $locationMatch
                ? "Found at campus #{$copyCampusId}, expected #{$stocktake->campus_id}"
                : null,
            'scanned_at'     => now(),
        ]);

        $bookTitle = $copy->book?->title ?? 'Unknown';
        $message = $locationMatch
            ? "✓ Scanned: {$bookTitle}"
            : "⚠ Location mismatch: {$bookTitle} — expected at different campus";

        return back()->with($locationMatch ? 'success' : 'error', $message);
    }

    /**
     * Complete a stocktake and generate summary.
     */
    public function complete(Request $request, Stocktake $stocktake)
    {
        abort_if($stocktake->status !== 'in_progress', 422, 'Stocktake is not active.');

        $stocktake->update([
            'status'       => 'completed',
            'completed_at' => now(),
        ]);

        return back()->with('success', 'Stocktake completed successfully.');
    }

    /**
     * Cancel an in-progress stocktake.
     */
    public function cancel(Request $request, Stocktake $stocktake)
    {
        abort_if($stocktake->status !== 'in_progress', 422, 'Stocktake is not active.');

        $stocktake->update(['status' => 'cancelled']);

        return redirect()->route('stocktakes.index')
            ->with('success', 'Stocktake cancelled.');
    }
}
