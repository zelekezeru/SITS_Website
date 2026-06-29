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
        // Get the counts of each model for administration analytics
        $coursesCount = Course::count();
        $trainersCount = Trainer::count();
        $programsCount = Program::count();
        $eventsCount = Event::count();
        $blogsCount = Blog::count();
        $usersCount = User::count();
        $librariesCount = Library::count();

        // Get system portals mapping
        $portals = config('portals', []);
        $user = auth()->user();

        foreach ($portals as $key => &$portal) {
            $hasAccess = false;
            // Check if user has any role authorized for this portal
            foreach ($portal['roles'] as $roleName) {
                if ($user->hasRole($roleName)) {
                    $hasAccess = true;
                    break;
                }
            }
            // Fallback for authenticated users who have no roles assigned yet (default to USER access)
            if (!$hasAccess && in_array('USER', $portal['roles']) && $user->roles->isEmpty()) {
                $hasAccess = true;
            }

            // Custom checks and URL routing for specific portals
            if ($key === 'library') {
                $hasAccess = $user->hasLibraryAccess();
                $portal['url'] = route('library.portal');
            } elseif ($key === 'lms') {
                $portal['url'] = route('lms.redirect');
            }

            $portal['authorized'] = $hasAccess;

            // Dynamically customize LMS name based on user type
            if ($key === 'lms') {
                if ($user->hasRole('STUDENT')) {
                    $portal['name'] = 'Student Learning Portal';
                } elseif ($user->hasRole('TRAINER')) {
                    $portal['name'] = 'Instructors Portal';
                } elseif ($user->hasAnyRole(['STAFF', 'SUPERADMIN', 'ADMIN', 'EDITOR', 'LIBRARIAN'])) {
                    $portal['name'] = 'Staff Portal';
                }
            }
        }
        unset($portal); // Break reference

        // Pass the counts and portals to the dashboard view
        return view('dashboard', compact(
            'coursesCount', 
            'programsCount', 
            'eventsCount', 
            'trainersCount', 
            'blogsCount', 
            'usersCount', 
            'librariesCount',
            'portals'
        ));
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
