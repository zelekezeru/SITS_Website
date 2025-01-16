<?php

namespace App\Http\Controllers;

use App\Models\Library;
use Illuminate\Http\Request;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;
use Spatie\Permission\Middleware\RoleMiddleware;
use App\Http\Requests\LibraryStoreRequest;
use App\Http\Requests\LibraryUpdateRequest;

class LibraryController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        $libraries = Library::all();

        return view('libraries.index', compact('libraries'));
    }

    /**
     * Display a listing of the resource.
     */
    public function list(): View
    {

    $libraries = Library::paginate(10); // Use pagination to avoid loading too many records at once

    return view('libraries.list', compact('libraries'));

    }


    /**
     * Show the form for creating a new resource.
     */
    public function create(): View
    {
        $library = new Library;

        return view('libraries.create', compact('library'));
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(LibraryStoreRequest $request) : RedirectResponse
    {
        dd($request->all());

        $data = $request->validated();

        if ($request->hasFile('banner')) {
            $bannerPath = $request->file('banner')->store('libraryBanners', 'public');
            $data['banner'] = $bannerPath;
        }

        Library::create($data);

        return redirect()->route('libraries.list')
            ->with('success', 'Library created successfully.');
    }

    /**
     * Display the specified resource.
     */
    public function show(Library $library)
    {
        //
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit(Library $library)
    {
        //
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, Library $library)
    {
        //
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(Library $library)
    {
        //
    }
}
