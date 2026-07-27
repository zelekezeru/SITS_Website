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

    public function index(Request $request): View
    {
        $query = User::with('employee')->where('id', '!=', Auth::id());

        if ($request->boolean('trashed')) {
            $query->onlyTrashed();
        }

        $users = $query->orderBy('name', 'asc')->paginate(10)->withQueryString();
        $roles = Role::all();

        return view('users.list', compact('users', 'roles'));
    }

    /**
     * Display a listing of the resource.
     */
    public function list(Request $request): View
    {
        $query = User::with('employee')->where('id', '!=', Auth::id());

        if ($request->boolean('trashed')) {
            $query->onlyTrashed();
        }

        $users = $query->orderBy('name', 'asc')->paginate(10)->withQueryString();
        $roles = Role::all();

        return view('users.list', compact('users', 'roles'));
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
        // Validate input data. Accept either a multi-role `roles[]` array or a
        // single legacy `role` string.
        $validatedData = $request->validate([
            'name' => 'required|string|max:255',
            'email' => 'required|email|max:255|unique:users,email,' . $user->id,
            'roles'   => ['required_without:role', 'array', 'min:1'],
            'roles.*' => ['string', 'exists:roles,name'],
            'role'    => ['required_without:roles', 'string', 'exists:roles,name'],
        ]);

        // Update user details
        $user->update([
            'name' => $validatedData['name'],
            'email' => $validatedData['email'],
        ]);

        // Sync user roles (full desired set)
        $user->syncRoles($validatedData['roles'] ?? [$validatedData['role']]);

        // Redirect with a success message
        return redirect()->route('users.list')
            ->with('success', 'User updated successfully');
    }


    public function destroy(User $user)
    {
        if ($user->hasRiskyData()) {
            $details = implode(' ', $user->getRiskyDataDetails());
            return redirect()->back()
                ->with('error', "Cannot delete user: {$details}");
        }

        $origEmail = $user->email;
        $user->safelySoftDelete();

        return redirect()->back()
            ->with('success', "User soft-deleted successfully. The email '{$origEmail}' has been freed for reuse.");
    }

    public function restore($id): RedirectResponse
    {
        $user = User::onlyTrashed()->findOrFail($id);
        $user->restore();
        $user->forceFill([
            'is_active'   => true,
            'is_approved' => true,
        ])->save();

        return redirect()->back()
            ->with('success', "User {$user->name} has been restored successfully.");
    }
}
