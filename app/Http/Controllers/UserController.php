<?php

namespace App\Http\Controllers;

// use App\Models\User;

use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\View\View;


use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Spatie\Permission\Models\Role;

class UserController extends Controller
{

    public function index(): View
    {
        $users = User::where('id', '!=', Auth::id())->orderBy('name', 'asc')->paginate(10); // Use pagination to avoid loading too many records at once

        return view('users.list', compact('users'));
    }

    /**
     * Display a listing of the resource.
     */
    public function list(): View
    {

        $users = User::where('id', '!=', Auth::id())->orderBy('name', 'asc')->paginate(10); // Use pagination to avoid loading too many records at once

        return view('users.list', compact('users'));
    }



    /**
     * Show the form for creating a new resource.
     */
    public function create(): RedirectResponse
    {
        return redirect(route('register'));
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request): RedirectResponse
    {
        User::create($request->validated());

        return redirect()->route('users.list')
            ->with('success', 'User created successfully.');
    }

    /**
     * Display the specified resource.
     */
    public function show($id): View
    {
        // Eager load for performance
        $user = User::findOrFail($id);
        
        return view('users.show', compact('user'));
    }
    /**
     * Show the form for editing the specified resource.
     */
    public function edit(User $user): View
    {
        $roles = Role::all();
        return view('users.edit', compact('user', 'roles'));
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, User $user): RedirectResponse
    {
        // Validate input data
        $validatedData = $request->validate([
            'name' => 'required|string|max:255',
            'email' => 'required|email|max:255|unique:users,email,' . $user->id,
            'role' => 'required|string|exists:roles,name',
        ]);

        // Update user details
        $user->update([
            'name' => $validatedData['name'],
            'email' => $validatedData['email'],
        ]);

        // Sync user role
        $user->syncRoles($validatedData['role']);

        // Redirect with a success message
        return redirect()->route('users.list')
            ->with('success', 'User updated successfully');
    }


    /**
     * Remove the specified resource from storage.
     */
    public function destroy(User $user)
    {
        $user->delete();

        return redirect()->route('users.list')
            ->with('success', 'User deleted successfully');
    }
}
