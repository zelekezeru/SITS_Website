<?php

/*
|--------------------------------------------------------------------------
| Moodle SSO — SITS as the OIDC identity provider
|--------------------------------------------------------------------------
| Moodle's core `auth_oauth2` plugin is the consumer: it sends the user to
| /oauth/authorize, exchanges the code at /oauth/token, then reads the profile
| from /oauth/userinfo with the Bearer token. These tests walk that exact
| sequence so a regression in scopes, the consent view, the userinfo claims or
| the guard shows up here instead of on the LMS login screen.
|
| See docs/moodle-integration.md.
*/

use App\Models\User;
use Laravel\Passport\ClientRepository;
use Laravel\Passport\Passport;
use Spatie\Permission\Models\Role;

use function Pest\Laravel\actingAs;
use function Pest\Laravel\get;
use function Pest\Laravel\post;

beforeEach(function () {
    // Passport needs signing keys; CI/fresh clones may not have run passport:keys.
    if (! file_exists(storage_path('oauth-private.key'))) {
        Illuminate\Support\Facades\Artisan::call('passport:keys', ['--no-interaction' => true]);
    }

    $this->client = app(ClientRepository::class)->createAuthorizationCodeGrantClient(
        'Moodle LMS',
        ['https://learn.sits.edu.et/admin/oauth2callback.php'],
        confidential: true,
    );
});

it('advertises the scopes Moodle requests', function () {
    expect(array_keys(Passport::scopes()->pluck('description', 'id')->all()))
        ->toContain('openid', 'profile', 'email');
});

it('walks the full authorization code flow and returns OIDC claims to Moodle', function () {
    $user = User::factory()->create([
        'name'              => 'Abebe Bikila',
        'email'             => 'abebe@sits.edu.et',
        'email_verified_at' => now(),
    ]);
    Role::findOrCreate('STUDENT');
    $user->assignRole('STUDENT');

    $redirect = $this->client->redirect_uris[0];
    $verifier = str_repeat('a', 64);
    $challenge = rtrim(strtr(base64_encode(hash('sha256', $verifier, true)), '+/', '-_'), '=');

    // 1. Moodle sends the user to /oauth/authorize — the consent screen renders.
    $authorize = actingAs($user)->get('/oauth/authorize?'.http_build_query([
        'client_id'             => $this->client->getKey(),
        'redirect_uri'          => $redirect,
        'response_type'         => 'code',
        'scope'                 => 'openid profile email',
        'state'                 => 'moodle-state',
        'code_challenge'        => $challenge,
        'code_challenge_method' => 'S256',
    ]));

    $authorize->assertOk();
    $authorize->assertSee('Moodle LMS');
    $authToken = $authorize->viewData('authToken');

    // 2. The user approves; Passport redirects back to Moodle with ?code=…
    $approve = actingAs($user)->post(route('passport.authorizations.approve'), [
        'state'      => 'moodle-state',
        'client_id'  => $this->client->getKey(),
        'auth_token' => $authToken,
    ]);

    $approve->assertRedirect();
    $location = $approve->headers->get('Location');
    expect($location)->toStartWith($redirect);
    parse_str(parse_url($location, PHP_URL_QUERY), $query);
    expect($query)->toHaveKey('code')
        ->and($query['state'])->toBe('moodle-state');

    // 3. Moodle exchanges the code for an access token.
    $token = post('/oauth/token', [
        'grant_type'    => 'authorization_code',
        'client_id'     => $this->client->getKey(),
        'client_secret' => $this->client->plainSecret ?? $this->client->secret,
        'redirect_uri'  => $redirect,
        'code_verifier' => $verifier,
        'code'          => $query['code'],
    ]);

    $token->assertOk();
    $accessToken = $token->json('access_token');
    expect($accessToken)->not->toBeEmpty();

    // 4. Moodle reads the profile it provisions the LMS account from.
    $userinfo = get('/oauth/userinfo', [
        'Authorization' => 'Bearer '.$accessToken,
        'Accept'        => 'application/json',
    ]);

    $userinfo->assertOk();
    $userinfo->assertJson([
        'sub'                => (string) $user->id,
        'email'              => 'abebe@sits.edu.et',
        'email_verified'     => true,
        'name'               => 'Abebe Bikila',
        'given_name'         => 'Abebe',
        'family_name'        => 'Bikila',
        'preferred_username' => 'abebe@sits.edu.et',
        'roles'              => ['STUDENT'],
    ]);
});

it('skips the consent screen for the trusted LMS client', function () {
    // With MOODLE_OAUTH_CLIENT_ID pointing at this client, a signed-in user must
    // go straight back to Moodle with a code — no "Authorize" button in between.
    config(['services.moodle.oauth_client_id' => (string) $this->client->getKey()]);

    $response = actingAs(User::factory()->create())->get('/oauth/authorize?'.http_build_query([
        'client_id'     => $this->client->getKey(),
        'redirect_uri'  => $this->client->redirect_uris[0],
        'response_type' => 'code',
        'scope'         => 'openid profile email',
        'state'         => 'moodle-state',
    ]));

    $response->assertRedirect();
    expect($response->headers->get('Location'))
        ->toStartWith($this->client->redirect_uris[0])
        ->toContain('code=');
});

it('still asks for consent for a client that is not the trusted LMS', function () {
    config(['services.moodle.oauth_client_id' => 'some-other-client']);

    actingAs(User::factory()->create())->get('/oauth/authorize?'.http_build_query([
        'client_id'     => $this->client->getKey(),
        'redirect_uri'  => $this->client->redirect_uris[0],
        'response_type' => 'code',
        'scope'         => 'openid profile email',
        'state'         => 'moodle-state',
    ]))->assertOk()->assertSee('Authorize');
});

it('sends a signed-out user to the SITS login and back to the consent screen', function () {
    // The common path: the user clicks "Log in with SITS" in Moodle without an
    // active SITS session. Passport must bounce them through /login and return.
    $url = '/oauth/authorize?'.http_build_query([
        'client_id'     => $this->client->getKey(),
        'redirect_uri'  => $this->client->redirect_uris[0],
        'response_type' => 'code',
        'scope'         => 'openid profile email',
        'state'         => 'moodle-state',
    ]);

    get($url)->assertRedirect(route('login'));

    // Intended URL is compared loosely: Laravel re-encodes the query string
    // (space → %20 rather than +), which is equivalent but not string-equal.
    expect(session('url.intended'))
        ->toContain('/oauth/authorize')
        ->toContain('client_id='.$this->client->getKey())
        ->toContain('state=moodle-state');
});

it('refuses userinfo without a bearer token', function () {
    get('/oauth/userinfo', ['Accept' => 'application/json'])->assertUnauthorized();
});

it('sends /go/lms straight to Moodle when no web-service token is configured', function () {
    config(['services.moodle.token' => '', 'services.moodle.url' => 'https://learn.sits.edu.et']);

    actingAs(User::factory()->create())
        ->get('/go/lms')
        ->assertRedirect('https://learn.sits.edu.et');
});
