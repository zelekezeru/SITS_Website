<?php

namespace App\Http\Controllers;

use App\Models\User;
use App\Support\RoleLanding;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Password;
use Illuminate\Validation\ValidationException;
use Inertia\Inertia;
use Spatie\Permission\Models\Role;

class AuthController extends Controller
{
    /**
     * Handle authentication attempts.
     */
    public function login(Request $request)
    {
        $credentials = $request->validate([
            'email' => ['required', 'email'],
            'password' => ['required'],
        ]);

        if (Auth::attempt($credentials, $request->boolean('remember'))) {
            /** @var User $user */
            $user = Auth::user();

            if (! $user->canLogin()) {
                Auth::logout();
                throw ValidationException::withMessages([
                    'email' => ['Your account is pending administrator approval or has been deactivated.'],
                ]);
            }

            $request->session()->regenerate();

            // Honor a pre-login intended URL; otherwise land on the role page.
            return redirect()->intended(RoleLanding::url($user));
        }

        throw ValidationException::withMessages([
            'email' => ['These credentials do not match our records.'],
        ]);
    }

    /**
     * Handle an incoming registration request.
     */
    public function register(Request $request)
    {
        $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'company' => ['required', 'string', 'max:255'],
            'email' => ['required', 'string', 'email', 'max:255', 'unique:users'],
            'password' => ['required', 'string', 'min:8', 'confirmed'],
            'terms' => ['accepted'],
        ]);

        $user = User::create([
            'name' => $request->name,
            'email' => $request->email,
            'password' => Hash::make($request->password),
            'is_approved' => false,
            'is_active' => true,
            // The user just chose this password themselves, so there's
            // nothing to force a change away from.
            'password_changed' => true,
        ]);

        // Assign default Employee role
        $role = Role::firstOrCreate(['name' => 'Employee']);
        $user->assignRole($role);

        // Create corresponding employee profile
        \App\Models\Employee::create([
            'user_id' => $user->id,
            'staff_no' => 'SITS-' . date('Y') . '-' . sprintf('%04d', $user->id),
            'full_name_en' => $user->name,
            'employment_type' => \App\Enums\EmploymentType::FullTime,
            'is_active' => true,
        ]);

        return redirect('/login')->with('success', 'Registration successful! Your account is pending administrator approval.');
    }

    /**
     * Show the "set a new password" form reached via an emailed recovery link.
     */
    public function showResetPassword(Request $request, string $token)
    {
        return Inertia::render('Auth/ResetPassword', [
            'token' => $token,
            'email' => $request->query('email', ''),
        ]);
    }

    /**
     * Complete a token-based password reset and let the user log back in.
     */
    public function resetPassword(Request $request)
    {
        $data = $request->validate([
            'token' => ['required'],
            'email' => ['required', 'email'],
            'password' => ['required', 'string', 'min:8', 'confirmed'],
        ]);

        $status = Password::reset($data, function (User $user, string $password) {
            $user->update([
                'password' => Hash::make($password),
                'password_changed' => true,
                'default_password' => null,
            ]);
        });

        if ($status !== Password::PASSWORD_RESET) {
            throw ValidationException::withMessages([
                'email' => [__($status)],
            ]);
        }

        return redirect('/login')->with('success', 'Your password has been reset. You can now sign in.');
    }

    /**
     * Approve a pending user account.
     */
    public function approve(Request $request, User $user)
    {
        /** @var User $currentUser */
        $currentUser = Auth::user();

        if (! $currentUser || ! $currentUser->hasRole('President / Super Admin')) {
            abort(403, 'Unauthorized action.');
        }

        $user->update([
            'is_approved' => true,
            'is_active' => true,
        ]);

        return redirect()->back()->with('success', "User account for {$user->name} has been approved.");
    }

    /**
     * Log the user out of the application.
     */
    public function logout(Request $request)
    {
        Auth::logout();

        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return redirect('/');
    }
}
