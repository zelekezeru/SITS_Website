<?php

namespace App\Http\Controllers;

use App\Models\Course;
use App\Models\Trainer;
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
        $trainers = Trainer::all();

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
        $tasksCount = Task::count(); // Count tasks
        $usersCount = User::count(); // Count users

        // Pass the counts to the dashboard view
        return view('dashboard', compact('coursesCount', 'programsCount', 'eventsCount', 'trainersCount', 'blogsCount', 'tasksCount', 'usersCount'));
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
