<?php

namespace App\Http\Controllers;

use App\Services\MoodleService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

class MoodleController extends Controller
{
    public function __construct(protected MoodleService $moodle) {}

    /**
     * Redirect the authenticated user to Moodle via SSO.
     * If SSO fails (Moodle down / token not set), fall back to direct URL.
     */
    public function redirect(Request $request)
    {
        $user   = $request->user();
        $direct = rtrim(config('services.moodle.url', 'https://lms.sits.edu.et'), '/');

        // If Moodle is not configured, just redirect to LMS directly
        if (empty(config('services.moodle.token'))) {
            return redirect()->away($direct);
        }

        try {
            $ssoUrl = $this->moodle->generateSSOUrl($user);
            return redirect()->away($ssoUrl);
        } catch (\Exception $e) {
            Log::error('Moodle SSO redirect failed', [
                'user'  => $user->email,
                'error' => $e->getMessage(),
            ]);
            // Graceful fallback — user lands on Moodle login page
            return redirect()->away($direct);
        }
    }
}
