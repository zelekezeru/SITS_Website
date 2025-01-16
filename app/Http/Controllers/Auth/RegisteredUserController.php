<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Auth\Events\Registered;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rules;
use Illuminate\View\View;
use Spatie\Permission\Models\Role;

class RegisteredUserController extends Controller
{
    /**
     * Display the registration view.
     */
    public function create(): View
    {

        $roles = Role::all();

        $user = User::first();

        $firstUser = $user === null;

        return view('auth.register', compact('roles', 'firstUser'));

    }

    /**
     * Handle an incoming registration request.
     *
     * @throws \Illuminate\Validation\ValidationException
     */
    public function store(Request $request): RedirectResponse
    {
        // dd(Role::find($request->input('role')));
        $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'string', 'lowercase', 'email', 'max:255', 'unique:' . User::class],
            'password' => ['required', 'confirmed', Rules\Password::defaults()],
            'role' => ['required', 'string', 'exists:roles,name'], // Validate role name
            'profile_image' => 'nullable|image|mimes:jpeg,png,jpg,gif|max:2048',
        ]);

        $path = null;

        if ($request->hasFile('profile_image')) {

            $path = $request->file('profile_image')->store('profile_images', 'public');
            
        }

        $user = User::create([
            'name' => $request->name,
            'email' => $request->email,
            'password' => Hash::make($request->password),
            'profile_image' => $path, // Store null if no image uploaded
        ]);

        if ($request->input('role')) {
            $user->assignRole($request->input('role')); // Assign by role name
        }


        $user = User::count();

        if ($user == 1) {
            $redirectTo = ('login');
        }

        else {
            $redirectTo = 'users.list';
        }

        event(new Registered($user));
        if (User::count() === 0) {
            Auth::login($user);
            $redirectTo = 'dashboard';
        }

        return redirect(route($redirectTo, absolute: false));
    }
}
