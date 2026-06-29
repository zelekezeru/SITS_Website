<?php

namespace App\Services;

use App\Models\User;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;

/**
 * MoodleService — integrates sits.edu.et with lms.sits.edu.et via Moodle REST API.
 *
 * Requires in .env:
 *   MOODLE_URL=https://lms.sits.edu.et
 *   MOODLE_TOKEN=<admin webservice token>
 *   MOODLE_SSO_SERVICE=sits_sso_service
 */
class MoodleService
{
    protected string $baseUrl;
    protected string $token;

    public function __construct()
    {
        $this->baseUrl = rtrim(config('services.moodle.url', ''), '/');
        $this->token   = config('services.moodle.token', '');
    }

    // ── SSO ───────────────────────────────────────────────────────────────────

    /**
     * Generate a Moodle SSO auto-login URL for the given user.
     * Uses the auth_userkey plugin which creates a short-lived login key.
     *
     * Steps:
     *  1. Ensure the Moodle account exists (create if not)
     *  2. Request a login key via the REST API
     *  3. Return the auto-login URL
     *
     * @throws \RuntimeException when Moodle API is unreachable or returns an error
     */
    public function generateSSOUrl(User $user): string
    {
        // 1. Ensure Moodle user exists / sync profile
        $this->ensureMoodleUser($user);

        // 2. Request login key
        $response = $this->call('auth_userkey_request_login_url', [
            'user' => [
                'username' => $this->toMoodleUsername($user),
                'email'    => $user->email,
            ],
        ]);

        if (isset($response['loginurl'])) {
            return $response['loginurl'];
        }

        // Fallback: direct Moodle URL (unauthenticated)
        Log::warning('Moodle SSO: could not generate login URL', ['response' => $response]);
        return $this->baseUrl;
    }

    // ── User Sync ─────────────────────────────────────────────────────────────

    /**
     * Create or update the Moodle account for a given Laravel user.
     * Called during SSO so the account always exists before login.
     */
    public function ensureMoodleUser(User $user): void
    {
        $existing = $this->findMoodleUserByEmail($user->email);

        if ($existing) {
            // Update profile if name changed
            $this->call('core_user_update_users', [
                'users' => [[
                    'id'        => $existing['id'],
                    'firstname' => $this->firstName($user->name),
                    'lastname'  => $this->lastName($user->name),
                ]],
            ]);
            return;
        }

        // Create new Moodle account
        $this->call('core_user_create_users', [
            'users' => [[
                'username'  => $this->toMoodleUsername($user),
                'firstname' => $this->firstName($user->name),
                'lastname'  => $this->lastName($user->name),
                'email'     => $user->email,
                'auth'      => 'userkey',               // auth_userkey plugin
                'password'  => '',                      // not needed for userkey auth
            ]],
        ]);
    }

    // ── Course Data ───────────────────────────────────────────────────────────

    /**
     * Return enrolled courses for the user — cached for 10 minutes.
     */
    public function getUserCourses(User $user): array
    {
        $cacheKey = "moodle_courses_{$user->id}";

        return Cache::remember($cacheKey, 600, function () use ($user) {
            try {
                $moodleUser = $this->findMoodleUserByEmail($user->email);
                if (! $moodleUser) {
                    return [];
                }

                $courses = $this->call('core_enrol_get_users_courses', [
                    'userid' => $moodleUser['id'],
                ]);

                return is_array($courses) ? $courses : [];
            } catch (\Exception $e) {
                Log::warning('Moodle: could not fetch courses', ['error' => $e->getMessage()]);
                return [];
            }
        });
    }

    // ── Site Stats (for homepage/dashboard) ───────────────────────────────────

    /**
     * Return total enrolled users and course count — cached 1 hour.
     */
    public function getSiteStats(): array
    {
        return Cache::remember('moodle_site_stats', 3600, function () {
            try {
                $info = $this->call('core_webservice_get_site_info');
                return [
                    'site_name'    => $info['sitename'] ?? 'SITS LMS',
                    'release'      => $info['release'] ?? '',
                ];
            } catch (\Exception $e) {
                return [];
            }
        });
    }

    // ── Helpers ───────────────────────────────────────────────────────────────

    protected function findMoodleUserByEmail(string $email): ?array
    {
        $response = $this->call('core_user_get_users', [
            'criteria' => [['key' => 'email', 'value' => $email]],
        ]);

        return $response['users'][0] ?? null;
    }

    protected function toMoodleUsername(User $user): string
    {
        // Use email prefix, sanitized — Moodle usernames must be lowercase alphanumeric
        return strtolower(preg_replace('/[^a-z0-9._-]/i', '', explode('@', $user->email)[0]));
    }

    protected function firstName(string $name): string
    {
        $parts = explode(' ', trim($name));
        return $parts[0] ?? $name;
    }

    protected function lastName(string $name): string
    {
        $parts = explode(' ', trim($name));
        return count($parts) > 1 ? implode(' ', array_slice($parts, 1)) : $parts[0];
    }

    /**
     * Make a Moodle REST API call.
     *
     * @param  string $function  Moodle web service function name
     * @param  array  $params    Parameters to pass
     * @return array             Decoded JSON response
     * @throws \RuntimeException on HTTP or Moodle-level errors
     */
    protected function call(string $function, array $params = []): array
    {
        $url = "{$this->baseUrl}/webservice/rest/server.php";

        $response = Http::timeout(10)->post($url, array_merge([
            'wstoken'       => $this->token,
            'wsfunction'    => $function,
            'moodlewsrestformat' => 'json',
        ], $params));

        if ($response->failed()) {
            throw new \RuntimeException("Moodle API HTTP error for {$function}: " . $response->status());
        }

        $data = $response->json();

        if (isset($data['exception'])) {
            throw new \RuntimeException("Moodle API error ({$function}): {$data['message']}");
        }

        return $data ?? [];
    }
}
