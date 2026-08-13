<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Support\RoleLanding;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Inertia\Inertia;
use Inertia\Response;

/**
 * Forces a one-time password change for ERP users still on their default or
 * recovery password (User::$password_changed === false). Separate from the
 * Breeze Auth\PasswordController (profile password update) to avoid a clash.
 */
class ForcePasswordController extends Controller
{
    public function showForceChange(): Response
    {
        return Inertia::render('Auth/ForceChangePassword');
    }

    public function forceChange(Request $request)
    {
        $request->validate([
            // Proving knowledge of the current password is what makes this a
            // *change* rather than a takeover: without it, anyone who reached a
            // session on a shared default could claim the account outright.
            'current_password' => ['required', 'string', 'current_password'],
            // Refusing the old value stops "change" being a no-op that leaves a
            // known default in place.
            'password' => ['required', 'string', 'min:8', 'confirmed', 'different:current_password'],
        ], [
            'current_password.current_password' => 'That is not your current password.',
            'password.different' => 'Your new password must be different from your current one.',
        ]);

        /** @var User $user */
        $user = Auth::user();

        $user->update([
            'password' => Hash::make($request->password),
            'password_changed' => true,
            'default_password' => null,
        ]);

        // New session id after a credential change, and re-hash the remembered
        // session so the user is not logged out by the password swap.
        $request->session()->regenerate();
        Auth::setUser($user);

        // Resume whatever they were doing — for the LMS this is the Moodle SSO
        // handshake they were pushed out of, so the password change costs them
        // one form and drops them where they were headed.
        return redirect()->intended(RoleLanding::url($user))
            ->with('success', 'Password updated. Welcome aboard!');
    }
}
