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
     * Show the form for creating a new resource.
     */
    public function create(): View
    {
        $course = new COurse;
        return view('courses.create', compact('course'));
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(CourseStoreRequest $request) : RedirectResponse
    {
        Course::create($request->validated());

        return redirect()->route('courses.index')
            ->with('success', 'Course created successfully.');
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
    public function update(CourseUpdateRequest $request, Course $course) : RedirectResponse
    {
        $data = $request->validated();
        $course->update($data);

        return redirect()->route('courses.index')
            ->with('success', 'Course updated successfully');
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(Course $course)
    {
        $course->delete();

        return redirect()->route('courses.index')
            ->with('success', 'Course deleted successfully');
    }
}
