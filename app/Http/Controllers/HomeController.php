<?php

namespace App\Http\Controllers;

use App\Models\Course;
use App\Models\Program;
use App\Models\Task;
use App\Models\Blog;
use App\Models\Event;
use App\Models\User;
use Illuminate\Http\Request;

class HomeController extends Controller
{
    public function index()
    {
        $courses = Course::all();
        $events = Event::latest()->take(2)->get();

        return view('index', compact('courses', 'events'));
    }
    public function dashboard()
    {
        // Get the counts of each model
        $coursesCount = Course::count(); // Count courses
        $programsCount = Program::count(); // Count programs
        $eventsCount = Event::count(); // Count events
        $blogsCount = Blog::count(); // Count blogs
        $tasksCount = Task::count(); // Count tasks
        $usersCount = User::count(); // Count users

        // Pass the counts to the dashboard view
        return view('dashboard', compact('coursesCount', 'programsCount', 'eventsCount', 'blogsCount', 'tasksCount', 'usersCount'));
    }

    public function about()
    {
        return view('abouts.about');
    }

    public function elements()
    {
        return view('layouts.elements');
    }
}
