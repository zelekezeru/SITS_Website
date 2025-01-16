<?php

namespace App\Http\Controllers;

use App\Models\Blog;
use Carbon\Carbon;
use Illuminate\Http\Request;

class BlogController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        $blogs = Blog::paginate(5);
        
        return view('blogs.index', compact('blogs'));
    }

    /**
     * Display a listing of the resource.
     */
    public function list()
    {
        $blogs = Blog::paginate(10);
        
        return view('blogs.list', compact('blogs'));
    }

    /**
     * Show the form for creating a new resource.
     */
    public function create()
    {
        return view('blogs.create');
    }

    /*
    * Store text editors upload
    */

    public function upload(Request $request)
    {
        $path = $request->file('upload')->store('blog_images/', 'public');
        $url = asset('storage/' . $path);

        return response()->json(['url' => $url, 'uploaded' => 1]);
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        $data = $request->validate([
            'title' => 'required|string|max:255',
            'category' => 'required|string|max:255',
            'author' => 'required|string|max:255',
            'content' => 'required|string',
        ]);

        $data['date'] = Carbon::now();

        $blog = Blog::create($data);

        return redirect(route('blogs.list'));
    }

    /**
     * Display the specified resource.
     */
    public function show(Blog $blog)
    {
        return view("blogs.show", compact('blog'));
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit(Blog $blog)
    {
        return view("blogs.edit", compact('blog'));
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, Blog $blog) 
    { 
        $data = $request->validate([ 
            'title' => 'required|string|max:255', 
            'category' => 'required|string|max:255', 
            'author' => 'required|string|max:255', 
            'content' => 'required|string', 
        ]); 
        $blog->update($data); 
        return redirect(route('blogs.list'))->with('message', 'Blog updated successfully!');
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(Blog $blog)
    {
        $blog->delete();

        return redirect(route('blogs.list'));
    }
}
