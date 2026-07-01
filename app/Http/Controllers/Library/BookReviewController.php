<?php

namespace App\Http\Controllers\Library;

use App\Http\Controllers\Controller;

use App\Models\Book;
use App\Models\BookReview;
use Illuminate\Http\Request;

class BookReviewController extends Controller
{
    /**
     * Store or update the authenticated user's review for a book.
     */
    public function store(Request $request, Book $book)
    {
        $request->validate([
            'rating' => 'required|integer|min:1|max:5',
            'review' => 'nullable|string|max:2000',
        ]);

        $review = BookReview::updateOrCreate(
            ['book_id' => $book->id, 'user_id' => $request->user()->id],
            [
                'rating'      => $request->rating,
                'review'      => $request->review,
                'is_approved' => false, // Reset approval on edit
            ]
        );

        return back()->with('success', 'Your review has been submitted for approval.');
    }

    /**
     * Delete the authenticated user's own review.
     */
    public function destroy(BookReview $review, Request $request)
    {
        abort_unless(
            $review->user_id === $request->user()->id || $request->user()->can('edit_book'),
            403
        );

        $review->delete();

        return back()->with('success', 'Review deleted.');
    }

    /**
     * Toggle approval status (admin only).
     */
    public function toggleApproval(BookReview $review, Request $request)
    {
        abort_unless($request->user()->can('edit_book'), 403);

        $review->update(['is_approved' => ! $review->is_approved]);

        $status = $review->is_approved ? 'approved' : 'unapproved';

        return back()->with('success', "Review {$status}.");
    }
}
