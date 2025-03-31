<?php

namespace App\Http\Controllers;

use App\Models\Gallery;
use App\Http\Requests\StoreGalleryRequest;
use App\Http\Requests\UpdateGalleryRequest;
use Illuminate\Http\Request;
use Illuminate\Http\RedirectResponse;
use Illuminate\View\View;
use Illuminate\Support\Facades\Storage;

class GalleryController extends Controller
{
    public function index()
    {
        $galleries = Gallery::paginate(10); // Fetch images from the database with pagination
        return view('galleries.index', compact('galleries')); // Note the 'abouts.gallery' path
    }
    
    
    public function list(): View
    {
        $galleries = Gallery::paginate(10);

        return view('galleries.list', compact('galleries'));
    }
        
    public function show($id)
    {
        
        $gallery = Gallery::findOrFail($id); 

        return view('galleries.show', compact('gallery'));
    }

    public function create()
    {
        return view('galleries.create');
    }

    public function store(StoreGalleryRequest $request)
    {
        $data = $request->validated();

        if ($request->hasFile('image')) {
            $data['image'] = $request->file('image')->store('galleries', 'public');
        }

        Gallery::create($data);

        return redirect()->route('galleries.list')->with('success', 'Gallery item added successfully.');
    }

    public function edit(Gallery $gallery)
    {
        return view('galleries.edit', compact('gallery'));
    }

    public function update(UpdateGalleryRequest $request, Gallery $gallery)
    {
        $data = $request->validated();

        if ($request->hasFile('image')) {
            Storage::disk('public')->delete($gallery->image);
            $data['image'] = $request->file('image')->store('galleries', 'public');
        }

        $gallery->update($data);

        return redirect()->route('galleries.list')->with('success', 'Gallery item updated successfully.');
    }

    public function destroy(Gallery $gallery)
    {
        Storage::disk('public')->delete($gallery->image);
        $gallery->delete();

        return redirect()->route('galleries.list')->with('success', 'Gallery item deleted successfully.');
    }
}
