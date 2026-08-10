<?php

/*
|--------------------------------------------------------------------------
| Moodle SSO — the zero-click /go/lms path (auth_userkey)
|--------------------------------------------------------------------------
| OIDC cannot be started from an external link: Moodle's auth/oauth2/login.php
| calls require_sesskey(), so a link from sits.edu.et can only land the user on
| Moodle's login page to press "Log in with SITS". The auth_userkey plugin is
| what makes the website link log the user straight in, and this covers that
| handshake with the Moodle API faked.
|
| See docs/moodle-sso-server-setup.md.
*/

use App\Models\User;
use Illuminate\Support\Facades\Http;

use function Pest\Laravel\actingAs;

beforeEach(function () {
    config([
        'services.moodle.url'               => 'https://learn.sits.edu.et',
        'services.moodle.token'             => 'test-token',
        'services.moodle.sso_mapping_field' => 'email',
    ]);
});

it('logs the user straight into Moodle with a one-time key', function () {
    $user = User::factory()->create(['name' => 'Abebe Bikila', 'email' => 'abebe@sits.edu.et']);

    Http::fake(function ($request) {
        $body = [];
        parse_str($request->body(), $body);

        return match ($body['wsfunction'] ?? null) {
            // Account already exists, so no creation call is expected.
            'core_user_get_users' => Http::response(['users' => [['id' => 42]]]),
            'core_user_update_users' => Http::response([]),
            'auth_userkey_request_login_url' => Http::response([
                'loginurl' => 'https://learn.sits.edu.et/auth/userkey/login.php?key=one-time-key',
            ]),
            default => Http::response([]),
        };
    });

    actingAs($user)->get('/go/lms')
        ->assertRedirect('https://learn.sits.edu.et/auth/userkey/login.php?key=one-time-key');

    // The login request must identify the user by the mapping field alone.
    Http::assertSent(function ($request) {
        $body = [];
        parse_str($request->body(), $body);

        return ($body['wsfunction'] ?? null) === 'auth_userkey_request_login_url'
            && ($body['user']['email'] ?? null) === 'abebe@sits.edu.et'
            && ! isset($body['user']['username']);
    });
});

it('provisions a missing Moodle account before logging in', function () {
    $user = User::factory()->create(['name' => 'Tirunesh Dibaba', 'email' => 'tirunesh@sits.edu.et']);

    Http::fake(function ($request) {
        $body = [];
        parse_str($request->body(), $body);

        return match ($body['wsfunction'] ?? null) {
            'core_user_get_users' => Http::response(['users' => []]),
            'core_user_create_users' => Http::response([['id' => 43]]),
            'auth_userkey_request_login_url' => Http::response(['loginurl' => 'https://learn.sits.edu.et/auth/userkey/login.php?key=k']),
            default => Http::response([]),
        };
    });

    actingAs($user)->get('/go/lms')->assertRedirect();

    Http::assertSent(function ($request) {
        $body = [];
        parse_str($request->body(), $body);

        return ($body['wsfunction'] ?? null) === 'core_user_create_users'
            && ($body['users'][0]['email'] ?? null) === 'tirunesh@sits.edu.et'
            && ($body['users'][0]['firstname'] ?? null) === 'Tirunesh'
            && ($body['users'][0]['lastname'] ?? null) === 'Dibaba'
            && ($body['users'][0]['auth'] ?? null) === 'userkey';
    });
});

it('falls back to the Moodle login page when the API errors', function () {
    // Moodle down or token revoked — the user must still reach the LMS and be
    // able to press "Log in with SITS", never see a SITS stack trace.
    Http::fake(fn () => Http::response(['exception' => 'moodle_exception', 'message' => 'Invalid token'], 200));

    actingAs(User::factory()->create())->get('/go/lms')
        ->assertRedirect('https://learn.sits.edu.et');
});

it('requires a signed-in SITS user', function () {
    $this->get('/go/lms')->assertRedirect(route('login'));
});
