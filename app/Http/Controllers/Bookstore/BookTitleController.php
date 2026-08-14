<?php

namespace App\Http\Controllers\Bookstore;

use App\Enums\Language;
use App\Http\Controllers\Controller;
use App\Models\BookTitle;
use App\Models\Course;
use App\Models\Program;
use App\Models\StudyMode;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Inertia\Inertia;
use Inertia\Response;

class BookTitleController extends Controller
{
    public function index(Request $request): Response
    {
        $filters = $request->only(['search', 'program_id', 'study_mode_id', 'language', 'stock']);

        $titles = BookTitle::query()
            ->with(['program:id,title', 'studyMode:id,name', 'stocks'])
            ->when($filters['search'] ?? null, fn ($q, $term) => $q->where(fn ($sub) => $sub
                ->where('title', 'like', "%{$term}%")
                ->orWhere('code', 'like', "%{$term}%")
                ->orWhere('author', 'like', "%{$term}%")
                ->orWhere('course_code', 'like', "%{$term}%")))
            ->forCategory(
                $filters['program_id'] ?? null,
                $filters['study_mode_id'] ?? null,
                $filters['language'] ?? null
            )
            ->when(($filters['stock'] ?? null) === 'low', fn ($q) => $q->lowStock())
            ->orderBy('title')
            ->paginate(20)
            ->withQueryString();

        return Inertia::render('Bookstore/Titles/Index', [
            'titles'  => $titles,
            'filters' => $filters,
            'options' => $this->formOptions(),
        ]);
    }

    public function create(): Response
    {
        return Inertia::render('Bookstore/Titles/Create', [
            'options' => $this->formOptions(),
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $validated = $this->validated($request);

        $title = BookTitle::create($validated);

        return redirect()->route('bookstore.titles.show', $title)
            ->with('success', 'Book title created. Print its QR label from this page.');
    }

    public function show(BookTitle $title): Response
    {
        $title->load([
            'program:id,title',
            'studyMode:id,name',
            'course:id,title',
            'stocks.shelfSection.shelf.storeRoom',
        ]);

        return Inertia::render('Bookstore/Titles/Show', [
            'title'     => $title,
            'movements' => $title->movements()
                ->with(['shelfSection.shelf', 'performedBy:id,name'])
                ->orderByDesc('occurred_at')
                ->orderByDesc('id')
                ->paginate(25),
            'printRuns' => $title->printRuns()
                ->with('shelfSection.shelf')
                ->orderByDesc('received_on')
                ->limit(10)
                ->get(),
            'stats' => [
                'on_hand'        => $title->total_on_hand,
                'reserved'       => $title->total_reserved,
                'available'      => $title->total_available,
                'low_stock'      => $title->isLowStock(),
                'weeks_of_cover' => $title->weeksOfCover(),
            ],
        ]);
    }

    public function edit(BookTitle $title): Response
    {
        return Inertia::render('Bookstore/Titles/Edit', [
            'title'   => $title,
            'options' => $this->formOptions(),
        ]);
    }

    public function update(Request $request, BookTitle $title): RedirectResponse
    {
        $title->update($this->validated($request, $title));

        return redirect()->route('bookstore.titles.show', $title)
            ->with('success', 'Book title updated.');
    }

    public function destroy(BookTitle $title): RedirectResponse
    {
        if ($title->total_on_hand > 0) {
            return back()->with('error', 'This title still has stock on the shelves. Move or write it off first.');
        }

        $title->delete();

        return redirect()->route('bookstore.titles.index')->with('success', 'Book title archived.');
    }

    /** @return array<string, mixed> */
    protected function validated(Request $request, ?BookTitle $title = null): array
    {
        $validated = $request->validate([
            'code'             => 'nullable|string|max:40|unique:book_titles,code'.($title ? ",{$title->id}" : ''),
            'title'            => 'required|string|max:255',
            'subtitle'         => 'nullable|string|max:255',
            'description'      => 'nullable|string',
            'author'           => 'nullable|string|max:255',
            'edition'          => 'nullable|string|max:60',
            'isbn'             => 'nullable|string|max:32',
            'course_id'        => 'nullable|exists:courses,id',
            'course_code'      => 'nullable|string|max:60',
            'course_name'      => 'nullable|string|max:255',
            'program_id'       => 'nullable|exists:programs,id',
            'language'         => 'required|string|in:'.implode(',', array_column(Language::cases(), 'value')),
            'study_mode_id'    => 'nullable|exists:study_modes,id',
            'page_count'       => 'nullable|integer|min:1',
            'unit_price'       => 'required|numeric|min:0',
            'unit_cost'        => 'nullable|numeric|min:0',
            'reorder_level'    => 'required|integer|min:0',
            'reorder_quantity' => 'nullable|integer|min:1',
            'is_active'        => 'boolean',
            'notes'            => 'nullable|string',
        ]);

        // `nullable` rules omit the key entirely when nothing is sent, so read
        // through ?? rather than indexing straight into $validated.

        // Keep the course code/name on the row: a book printed under an old
        // course code must still print correctly after the course is renamed.
        if (! empty($validated['course_id'])) {
            $course = Course::find($validated['course_id']);
            $validated['course_name'] = ($validated['course_name'] ?? null) ?: $course?->title;
        }

        $validated['code'] = ($validated['code'] ?? null) ?: $this->generateCode($validated);

        return $validated;
    }

    /** `{PROGRAM}-{SEQ}`, e.g. `SM-02`, matching the sticky labels already in use. */
    protected function generateCode(array $attributes): string
    {
        $program = Program::find($attributes['program_id'] ?? null);

        $prefix = Str::upper(Str::substr(
            preg_replace('/[^A-Za-z]/', '', $program?->code ?: $attributes['title']) ?: 'BK',
            0,
            2
        ));

        $sequence = BookTitle::withTrashed()->where('code', 'like', $prefix.'-%')->count() + 1;

        do {
            $code = $prefix.'-'.str_pad((string) $sequence, 2, '0', STR_PAD_LEFT);
            $sequence++;
        } while (BookTitle::withTrashed()->where('code', $code)->exists());

        return $code;
    }

    /** @return array<string, mixed> */
    protected function formOptions(): array
    {
        return [
            'programs'    => Program::orderBy('title')->get(['id', 'title', 'code']),
            'studyModes'  => StudyMode::active()->ordered()->get(['id', 'name', 'code']),
            'courses'     => Course::orderBy('title')->get(['id', 'title']),
            'languages'   => collect(Language::cases())
                ->map(fn (Language $l) => ['value' => $l->value, 'label' => $l->label()])
                ->values(),
        ];
    }
}
