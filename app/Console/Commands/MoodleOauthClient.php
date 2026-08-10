<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Laravel\Passport\ClientRepository;
use Laravel\Passport\Passport;

/**
 * Provision (or re-print) the Passport OAuth2 client that Moodle authenticates with.
 *
 * Run this on the server after deploying — it is idempotent, so re-running it
 * updates the redirect URI in place and shows the same credentials rather than
 * issuing a second client. The printed id/secret go into Moodle:
 * Site administration → Server → OAuth 2 services.
 */
class MoodleOauthClient extends Command
{
    protected $signature = 'moodle:oauth-client
                            {--name=Moodle LMS : Client name, also used to find an existing client}
                            {--redirect= : Moodle OAuth2 callback URL; defaults to MOODLE_URL/admin/oauth2callback.php}
                            {--rotate-secret : Issue a new client secret (you must then update it in Moodle)}';

    protected $description = 'Create or show the OAuth2 client Moodle uses for SITS single sign-on';

    public function handle(ClientRepository $clients): int
    {
        $name     = (string) $this->option('name');
        $moodle   = rtrim((string) config('services.moodle.url'), '/');
        $redirect = (string) ($this->option('redirect') ?: $moodle.'/admin/oauth2callback.php');

        if (! str_starts_with($redirect, 'https://')) {
            $this->error("Refusing to register a non-HTTPS redirect URI: {$redirect}");
            $this->line('Moodle sends the authorization code to this URL — it must be TLS.');

            return self::FAILURE;
        }

        $client = Passport::client()->newQuery()->where('name', $name)->first();

        if ($client) {
            $client->forceFill(['redirect_uris' => [$redirect], 'revoked' => false]);

            if ($this->option('rotate-secret')) {
                // Secrets are stored in plain text on purpose so Moodle can
                // authenticate — see AppServiceProvider. Rotating invalidates the
                // value currently configured in Moodle.
                $client->forceFill(['secret' => \Illuminate\Support\Str::random(40)]);
                $this->warn('Secret rotated — update it in Moodle now or SSO will break.');
            }

            $client->save();
            $this->info("Updated existing OAuth client \"{$name}\".");
        } else {
            $client = $clients->createAuthorizationCodeGrantClient($name, [$redirect], confidential: true);
            $this->info("Created OAuth client \"{$name}\".");
        }

        $secret = $client->plainSecret ?? $client->secret;

        $this->newLine();
        $this->line('Configure in Moodle → Server → OAuth 2 services → Custom service:');
        $this->table(['Field', 'Value'], [
            ['Client ID',              $client->getKey()],
            ['Client secret',          $secret],
            ['Authorization endpoint', url('/oauth/authorize')],
            ['Token endpoint',         url('/oauth/token')],
            ['Userinfo endpoint',      url('/oauth/userinfo')],
            ['Scopes',                 'openid profile email'],
            ['Redirect URI (Moodle)',  $redirect],
        ]);

        $this->newLine();
        $this->line('Then add this to the SITS .env and run `php artisan config:cache`,');
        $this->line('so the LMS is trusted and users skip the consent screen:');
        $this->newLine();
        $this->line('    MOODLE_OAUTH_CLIENT_ID='.$client->getKey());

        if ((string) config('services.moodle.oauth_client_id') !== (string) $client->getKey()) {
            $this->newLine();
            $this->warn('MOODLE_OAUTH_CLIENT_ID is not set to this client yet — until it is,');
            $this->warn('users will see a consent screen on their first login to Moodle.');
        }

        return self::SUCCESS;
    }
}
