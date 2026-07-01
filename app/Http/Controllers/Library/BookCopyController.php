<?php

namespace App\Http\Controllers\Library;

use App\Http\Controllers\Controller;

use App\Models\BookCopy;
use App\Models\Book;
use Illuminate\Http\Request;
use SimpleSoftwareIO\QrCode\Facades\QrCode;
use Inertia\Inertia;

class BookCopyController extends Controller
{
    public function store(Request $request, Book $book)
    {
        $validated = $request->validate([
            'home_campus_id' => 'nullable|exists:campuses,id',
            'current_shelf_box_id' => 'nullable|exists:shelf_boxes,id',
            'barcode' => 'nullable|string|unique:book_copies',
            'accession_number' => 'nullable|string|unique:book_copies',
            'condition' => 'in:new,good,fair,poor',
            'acquired_on' => 'nullable|date',
            'acquisition_cost' => 'nullable|numeric',
        ]);

        $book->copies()->create($validated);

        return back()->with('success', 'Copy added successfully.');
    }

    public function withdraw(BookCopy $copy)
    {
        $copy->update(['status' => 'withdrawn']);
        return back()->with('success', 'Copy marked as withdrawn.');
    }

    public function markLost(BookCopy $copy)
    {
        $copy->update(['status' => 'lost']);
        return back()->with('success', 'Copy marked as lost.');
    }

    public function qr(BookCopy $copy)
    {
        $svg = QrCode::format('svg')
            ->size(300)
            ->errorCorrection('M')
            ->generate($copy->tracking_hash);

        return response($svg, 200, [
            'Content-Type' => 'image/svg+xml',
            'Content-Disposition' => 'inline; filename="copy-'.$copy->tracking_hash.'.svg"',
        ]);
    }

    public function printLabels(Request $request)
    {
        $query = BookCopy::with('book');
        
        if ($ids = $request->input('ids')) {
            $query->whereIn('id', explode(',', $ids));
        }

        return Inertia::render('Library/BookCopies/Print', [
            'copies' => $query->get(),
        ]);
    }
}
