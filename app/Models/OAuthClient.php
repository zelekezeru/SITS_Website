<?php

namespace App\Models;

use Illuminate\Contracts\Auth\Authenticatable;
use Laravel\Passport\Client as PassportClient;

/**
 * Passport OAuth client with a "trusted" escape hatch for our own LMS.
 *
 * Moodle is not a third-party app asking for a slice of someone's account — it is
 * the same institution's LMS. Showing the SITS user a consent screen on the way
 * to their own courses is friction with no security value, so the Moodle client
 * skips the prompt and Passport approves the authorization request immediately.
 *
 * Trust is opt-in per client id via MOODLE_OAUTH_CLIENT_ID; every other client
 * (present or future) still gets the normal consent screen.
 *
 * Registered with Passport::useClientModel() in AppServiceProvider.
 */
class OAuthClient extends PassportClient
{
    /**
     * Skip the consent prompt for the trusted first-party LMS client.
     *
     * @param  \Laravel\Passport\Scope[]  $scopes
     */
    public function skipsAuthorization(Authenticatable $user, array $scopes): bool
    {
        $trusted = (string) config('services.moodle.oauth_client_id', '');

        return $trusted !== '' && (string) $this->getKey() === $trusted;
    }
}
