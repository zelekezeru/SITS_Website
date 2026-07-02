<?php

namespace App\Http\Controllers;

use App\Http\Requests\ProfileUpdateRequest;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Redirect;
use Illuminate\Support\Facades\Storage;
use Illuminate\View\View;
use Spatie\Permission\Models\Role;

class ProfileController extends Controller
{
    /**
     * Display the user's profile form.
     */
    public function edit(Request $request): View
    {
        $user = $request->user();
        $roles = Role::all();
        
        // Eager load ERP employee profile relationships
        $employee = $user->employee?->load(['position', 'department.campus', 'reportingTo']);
        
        // Library access details and stats
        $libraryAccess = $user->hasLibraryAccess();
        $activeSubscription = $user->activeLibrarySubscription();
        $activeLoansCount = $user->loans()->where('status', 'active')->count();
        $outstandingFines = $user->outstanding_fines_total;
        
        // Dynamic LMS labels and links
        $rolesLower = $user->roles->pluck('name')->map(fn($r) => strtolower($r));
        if ($rolesLower->contains('student')) {
            $lmsLabel = 'Student Learning Portal';
            $lmsUrl = '/go/lms';
        } elseif ($rolesLower->contains('trainer')) {
            $lmsLabel = 'Instructor Portal';
            $lmsUrl = '/go/lms';
        } else {
            $lmsLabel = 'LMS Portal';
            $lmsUrl = 'https://lms.sits.edu.et';
        }

        return view('profile.edit', compact(
            'roles',
            'user',
            'employee',
            'libraryAccess',
            'activeSubscription',
            'activeLoansCount',
            'outstandingFines',
            'lmsLabel',
            'lmsUrl'
        ));
    }

    /**
     * Update the user's profile information.
     */
    public function update(ProfileUpdateRequest $request): RedirectResponse
    {
        $user = $request->user();
        $user->fill($request->validated());

        if ($user->isDirty('email')) {
            $user->email_verified_at = null;
        }

        $user->save();

        return Redirect::route('profile.edit')->with('status', 'profile-updated');
    }

    /**
     * Upload and update user profile picture.
     */
    public function uploadProfileImage(Request $request): RedirectResponse
    {
        $request->validate([
            'profile_image' => ['required', 'image', 'max:2048'],
        ]);

        $user = $request->user();

        if ($request->hasFile('profile_image')) {
            // Delete old profile image if it exists
            if ($user->profile_image) {
                Storage::disk('public')->delete($user->profile_image);
            }

            $path = $request->file('profile_image')->store('profile-images', 'public');
            $user->profile_image = $path;
            $user->save();
        }

        return Redirect::route('profile.edit')->with('status', 'profile-updated');
    }

    /**
     * Delete the user's account.
     */
    public function destroy(Request $request): RedirectResponse
    {
        $request->validateWithBag('userDeletion', [
            'password' => ['required', 'current_password'],
        ]);

        $user = $request->user();

        Auth::logout();

        $user->delete();

        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return Redirect::to('/');
    }
}

