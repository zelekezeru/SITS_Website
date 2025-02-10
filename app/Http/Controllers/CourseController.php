<?php

namespace App\Http\Controllers;

use App\Models\Course;
use Illuminate\Http\RedirectResponse;
use App\Http\Requests\CourseStoreRequest;
use App\Http\Requests\CourseUpdateRequest;
use Illuminate\Http\Request;
use Illuminate\View\View;

class CourseController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index(): View
    {

    $courses = Course::paginate(10); // Use pagination to avoid loading too many records at once

    return view('courses.index', compact('courses'));

    }

    /**
     * Display a listing of the resource.
     */
    public function list(): View
    {

    $courses = Course::paginate(10); // Use pagination to avoid loading too many records at once

    return view('courses.list', compact('courses'));

    }



    /**
     * Show the form for creating a new resource.
     */
    public function create(): View
    {
        $course = new Course;
        return view('courses.create', compact('course'));
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(CourseStoreRequest $request) : RedirectResponse
    {
        $data = $request->validated();

        if ($request->hasFile('banner')) {
            $bannerPath = $request->file('banner')->store('courseBanners', 'public');
            $data['banner'] = $bannerPath;
        }

        Course::create($data);

        return redirect()->route('courses.list')
            ->with('status', 'Course created successfully.');
    }

    /**
     * Display the specified resource.
     */
    public function show(Course $course) : View
    {
        return view('courses.show', compact('course'));
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit(Course $course) : View
    {
        return view('courses.edit', compact('course'));
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(CourseUpdateRequest $request, Course $course): RedirectResponse
    {
        $data = $request->validated();

        if ($request->hasFile('banner')) {
            $bannerPath = $request->file('banner')->store('banners', 'public');
            $data['banner'] = $bannerPath;
        }

        $course->update($data);

        return redirect()->route('courses.list')
            ->with('status', 'Course updated successfully');
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(Course $course)
    {
        $course->delete();

        return redirect()->route('courses.list')
            ->with('status', 'Course deleted successfully');
    }
}
