<?php

namespace App\Http\Controllers;

use App\Models\Gallery;
use Illuminate\Http\Request;
use App\Http\Requests\GalleryRequest;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;
use Spatie\Permission\Middleware\RoleMiddleware;

class GalleryController extends Controller
{
    public function index()
    {
        
        $galleries = Gallery::all();
        
        return view('galleries.index', compact('galleries'));
    }
    
    public function list()
    {
        $galleries = Gallery::latest()->paginate(10);

        return view('galleries.list', compact('galleries'));

    }

    public function create()
    {
        return view('galleries.create');
    }

    public function store(GalleryRequest $request)
    {
        $imagePath = $request->file('image')->store('galleries', 'public');

        Gallery::create([
            'image' => $imagePath,
            'description' => $request->description,
            'title' => $request->title,
        ]);

        return redirect()->route('galleries.list')->with('status', 'Gallery image uploaded successfully.');
    }

    public function show(Gallery $gallery)
    {
        return view('galleries.show', compact('gallery'));
    }

    public function edit(Gallery $gallery)
    {
        return view('galleries.edit', compact('gallery'));
    }

    public function update(GalleryRequest $request, Gallery $gallery)
    {
        if ($request->hasFile('image')) {
            $imagePath = $request->file('image')->store('galleries', 'public');
            $gallery->image = $imagePath;
        }

        $gallery->description = $request->description;
        $gallery->title = $request->title;
        $gallery->save();

        return redirect()->route('galleries.list')->with('status', 'Gallery updated successfully.');
    }

    public function destroy(Gallery $gallery)
    {
        $gallery->delete();
        return redirect()->route('galleries.list')->with('status', 'Gallery deleted successfully.');
    }
}