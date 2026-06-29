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
            'password' => ['required', 'string', 'min:8', 'confirmed'],
        ]);

        /** @var User $user */
        $user = Auth::user();

        $user->update([
            'password' => Hash::make($request->password),
            'password_changed' => true,
            'default_password' => null,
        ]);

        $request->session()->regenerate();

        return redirect(RoleLanding::url($user))->with('success', 'Password updated. Welcome aboard!');
    }
}
