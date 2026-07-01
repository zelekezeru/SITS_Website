<?php

namespace App\Http\Controllers\Library;

use App\Http\Controllers\Controller;

use App\Models\Book;
use App\Models\Campus;
use Illuminate\Http\Request;
use Inertia\Inertia;

class CatalogController extends Controller
{
    public function index(Request $request)
    {
        $campusId = $request->integer('campus_id') ?: (auth()->user()->current_campus_id ?? Campus::first()?->id);
        $q        = $request->string('q')->trim();

        $query = Book::query()
            ->with(['authors:id,name','categories:id,name'])
            ->withCount(['copies as available_at_campus' => fn($c) =>
                $c->where('status','available')
                  ->whereHas('shelfBox.row.floor', fn($s) => $s->where('campus_id', $campusId))
            ]);

        $books = $q->isNotEmpty()
            ? Book::search($q)
                ->query(fn($qb) => $qb->with(['authors:id,name','categories:id,name'])
                    ->withCount(['copies as available_at_campus' => fn($c) =>
                        $c->where('status','available')
                          ->whereHas('shelfBox.row.floor', fn($s) => $s->where('campus_id', $campusId))
                    ])
                )
                ->paginate(25)
            : $query->orderBy('title')->paginate(25);

        $books->withQueryString();

        return Inertia::render('Catalog/Index', [
            'books'   => $books,
            'filters' => ['q' => (string)$q, 'campus_id' => $campusId],
            'campuses'=> Campus::orderBy('name')->get(['id','name','code']),
        ]);
    }

    public function show(Book $book)
    {
        $userId = auth()->id();
        $canModerate = auth()->user()?->can('edit_book');

        $book->load([
            'authors',
            'categories',
            'copies.shelfBox.row.floor.campus',
            'secureDocuments',
            'reviews' => function ($query) use ($canModerate, $userId) {
                if (!$canModerate) {
                    $query->where(function ($q) use ($userId) {
                        $q->where('is_approved', true);
                        if ($userId) {
                            $q->orWhere('user_id', $userId);
                        }
                    });
                }
                $query->with('user:id,name')->latest();
            }
        ]);

        $book->append('average_rating');

        return Inertia::render('Catalog/Show', [
            'book' => $book,
            'canModerateReviews' => $canModerate,
            'campuses' => Campus::orderBy('name')->get(['id', 'name', 'code']),
        ]);
    }
}
