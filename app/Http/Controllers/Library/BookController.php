<?php

namespace App\Http\Controllers\Library;

use App\Http\Controllers\Controller;

use App\Models\Book;
use App\Models\Author;
use App\Models\Category;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Inertia\Inertia;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;

class BookController extends Controller
{
    use AuthorizesRequests;

    public function index()
    {
        // Books are now browsed via CatalogController
        return redirect()->route('catalog.index');
    }

    public function create()
    {
        return Inertia::render('Books/Create', [
            'book' => new Book(),
        ]);
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'title' => 'required|string|max:255',
            'subtitle' => 'nullable|string|max:255',
            'isbn' => 'nullable|string|max:20',
            'call_number' => 'nullable|string|max:50',
            'classification_type' => 'nullable|string|in:dewey,loc',
            'publisher' => 'nullable|string|max:255',
            'published_year' => 'nullable|integer',
            'edition' => 'nullable|string|max:255',
            'page_count' => 'nullable|integer|min:1',
            'language' => 'nullable|string|max:10',
            'subject' => 'nullable|string|max:255',
            'description' => 'nullable|string',
            'cover_url' => 'nullable|url|max:2048',
            'notes' => 'nullable|string',
            'author_ids' => 'array',
            'author_ids.*' => 'exists:authors,id',
            'category_ids' => 'array',
            'category_ids.*' => 'exists:categories,id',
        ]);

        $book = DB::transaction(function () use ($validated) {
            $book = Book::create($validated);

            if (isset($validated['author_ids'])) {
                $book->authors()->sync($validated['author_ids']);
            }
            if (isset($validated['category_ids'])) {
                $book->categories()->sync($validated['category_ids']);
            }

            return $book;
        });

        return redirect()->route('catalog.show', $book)
            ->with('success', 'Book created successfully.');
    }

    public function edit(Book $book)
    {
        $book->load(['authors', 'categories']);
        return Inertia::render('Books/Edit', [
            'book' => $book,
        ]);
    }

    public function update(Request $request, Book $book)
    {
        $validated = $request->validate([
            'title' => 'required|string|max:255',
            'subtitle' => 'nullable|string|max:255',
            'isbn' => 'nullable|string|max:20',
            'call_number' => 'nullable|string|max:50',
            'classification_type' => 'nullable|string|in:dewey,loc',
            'publisher' => 'nullable|string|max:255',
            'published_year' => 'nullable|integer',
            'edition' => 'nullable|string|max:255',
            'page_count' => 'nullable|integer|min:1',
            'language' => 'nullable|string|max:10',
            'subject' => 'nullable|string|max:255',
            'description' => 'nullable|string',
            'cover_url' => 'nullable|url|max:2048',
            'notes' => 'nullable|string',
            'author_ids' => 'array',
            'author_ids.*' => 'exists:authors,id',
            'category_ids' => 'array',
            'category_ids.*' => 'exists:categories,id',
        ]);

        DB::transaction(function () use ($validated, $book) {
            $book->update($validated);

            if (isset($validated['author_ids'])) {
                $book->authors()->sync($validated['author_ids']);
            }
            if (isset($validated['category_ids'])) {
                $book->categories()->sync($validated['category_ids']);
            }
        });

        return redirect()->route('catalog.show', $book)
            ->with('success', 'Book updated successfully.');
    }

    public function destroy(Book $book)
    {
        $book->delete();

        return redirect()->route('catalog.index')
            ->with('success', 'Book deleted successfully.');
    }
}
