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
use Inertia\Inertia;

class HomeController extends Controller
{
    public function index()
    {
        $courses = Course::select(['id', 'title', 'category', 'description', 'banner'])
            ->latest()
            ->take(6)
            ->get();

        $trainers = Trainer::select(['id', 'name', 'position', 'description', 'image'])
            ->latest()
            ->take(4)
            ->get();

        $events = Event::select(['id', 'title', 'banner', 'date', 'start_time', 'end_time', 'location', 'description'])
            ->latest()
            ->take(2)
            ->get();

        return Inertia::render('Website/Home', [
            'courses'  => $courses,
            'trainers' => $trainers,
            'events'   => $events,
        ]);
    }
    public function dashboard()
    {
        $user = auth()->user();

        // Redirect employees directly to their ERP landing page
        if ($user && !$user->roles->isEmpty() && !$user->hasRole('STUDENT')) {
            return redirect(\App\Support\RoleLanding::url($user));
        }

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
                // Merged ILS: reachable by any user with the base library permission
                // (or the legacy subscription/admin path).
                $hasAccess = $user->can('view_books') || $user->hasLibraryAccess();
                $portal['url'] = route('library.dashboard');
            } elseif ($key === 'lms') {
                $portal['url'] = route('lms.redirect');
            } elseif ($key === 'erp') {
                $portal['url'] = \App\Support\RoleLanding::url($user);
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
        $galleries = Gallery::select(['id', 'image', 'description'])
            ->latest()
            ->take(12)
            ->get();
        return Inertia::render('Website/About/Index', ['galleries' => $galleries]);
    }

    public function elements()
    {
        return view('layouts.elements');
    }
}
