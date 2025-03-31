<?php

namespace App\Http\Controllers;

use App\Models\Course;
use App\Models\Trainer;
use App\Models\Program;
use App\Models\Event;
use App\Models\Blog;
use App\Models\Gallery;
use App\Models\User;
use App\Models\Library;
use Illuminate\Http\Request;

class HomeController extends Controller
{
    public function index()
    {

        $courses = Course::get();

        $trainers = Trainer::get();

        $courses = Course::get();

        $events = Event::latest()->take(2)->get();

        return view('index', compact('courses', 'events', 'trainers'));
    }
    public function dashboard()
    {
        // Get the counts of each model
        $coursesCount = Course::count(); // Count courses
        $trainersCount = Trainer::count(); // Count Trrainer
        $programsCount = Program::count(); // Count programs
        $eventsCount = Event::count(); // Count events
        $blogsCount = Blog::count(); // Count blogs
        $usersCount = User::count(); // Count users
        $librariesCount = Library::count(); // Count libraries

        // Pass the counts to the dashboard view
        return view('dashboard', compact('coursesCount', 'programsCount', 'eventsCount', 'trainersCount', 'blogsCount', 'usersCount', 'librariesCount'));
    }

    public function about()
    {
        $galleries = Gallery::all();
        return view('abouts.about', compact('galleries'));
    }

    public function elements()
    {
        return view('layouts.elements');
    }
}
