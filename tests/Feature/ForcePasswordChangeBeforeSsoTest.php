<?php

/*
|--------------------------------------------------------------------------
| Forced password change gates the single sign-on into Moodle
|--------------------------------------------------------------------------
| Users imported from the old Moodle share one default password, so reaching
| the LMS while still on it would mean anyone knowing that default could read
| someone else's grades and submissions. Both SSO entry points are therefore
| behind `password.fresh`: /go/lms (the website link) and /oauth/authorize
| (Moodle's own "Log in with SITS" button).
|
| Changing the password requires the OLD one, which is what makes it a change
| rather than a takeover of a session that arrived on a shared default.
*/

use App\Models\User;
use Illuminate\Support\Facades\Hash;
use Laravel\Passport\ClientRepository;

use function Pest\Laravel\actingAs;
use function Pest\Laravel\get;
use function Pest\Laravel\post;

beforeEach(function () {
    if (! file_exists(storage_path('oauth-private.key'))) {
        Illuminate\Support\Facades\Artisan::call('passport:keys', ['--no-interaction' => true]);
    }
});

it('blocks the LMS link while the user is still on a default password', function () {
    $user = User::factory()->create([
        'is_approved' => true,
        'is_active' => true,
        'password_changed' => false,
    ]);

    actingAs($user)->get('/go/lms')->assertRedirect(route('password.force-change'));
});

it('lets the LMS link through once the password has been changed', function () {
    config(['services.moodle.token' => '', 'services.moodle.url' => 'https://learn.sits.edu.et']);

    $user = User::factory()->create([
        'is_approved' => true,
        'is_active' => true,
        'password_changed' => true,
    ]);

    actingAs($user)->get('/go/lms')->assertRedirect('https://learn.sits.edu.et');
});

it('blocks Moodle\'s own login button at /oauth/authorize', function () {
    // The route Moodle sends users to. Without the gate a default-password user
    // would sail straight through into their courses.
    $client = app(ClientRepository::class)->createAuthorizationCodeGrantClient(
        'Moodle LMS',
        ['https://learn.sits.edu.et/admin/oauth2callback.php'],
        confidential: true,
    );

    $user = User::factory()->create([
        'is_approved' => true,
        'is_active' => true,
        'password_changed' => false,
    ]);

    actingAs($user)->get('/oauth/authorize?'.http_build_query([
        'client_id'     => $client->getKey(),
        'redirect_uri'  => $client->redirect_uris[0],
        'response_type' => 'code',
        'scope'         => 'openid profile email',
        'state'         => 'moodle-state',
    ]))->assertRedirect(route('password.force-change'));
});

it('rejects the change when the old password is wrong', function () {
    $user = User::factory()->create([
        'is_approved' => true,
        'is_active' => true,
        'password' => Hash::make('OldPassword1'),
        'password_changed' => false,
    ]);

    actingAs($user)->from(route('password.force-change'))->post('/password/force-change', [
        'current_password' => 'NotTheOldOne',
        'password' => 'BrandNewPass1',
        'password_confirmation' => 'BrandNewPass1',
    ])->assertSessionHasErrors('current_password');

    expect($user->fresh()->password_changed)->toBeFalse();
});

it('rejects reusing the same password, which would leave the default in place', function () {
    $user = User::factory()->create([
        'is_approved' => true,
        'is_active' => true,
        'password' => Hash::make('ChangeMe@2026'),
        'password_changed' => false,
    ]);

    actingAs($user)->from(route('password.force-change'))->post('/password/force-change', [
        'current_password' => 'ChangeMe@2026',
        'password' => 'ChangeMe@2026',
        'password_confirmation' => 'ChangeMe@2026',
    ])->assertSessionHasErrors('password');

    expect($user->fresh()->password_changed)->toBeFalse();
});

it('accepts the change with the correct old password and clears the stored default', function () {
    $user = User::factory()->create([
        'is_approved' => true,
        'is_active' => true,
        'password' => Hash::make('ChangeMe@2026'),
        'password_changed' => false,
        'default_password' => 'ChangeMe@2026',
    ]);

    actingAs($user)->post('/password/force-change', [
        'current_password' => 'ChangeMe@2026',
        'password' => 'MyOwnPassword1',
        'password_confirmation' => 'MyOwnPassword1',
    ])->assertRedirect();

    $user->refresh();

    expect($user->password_changed)->toBeTrue()
        ->and($user->default_password)->toBeNull()
        ->and(Hash::check('MyOwnPassword1', $user->password))->toBeTrue();
});

it('returns the user to the LMS they were heading for after changing', function () {
    // The point of the whole flow: one login, one password change, and they
    // land where they were going instead of having to start over.
    config(['services.moodle.token' => '', 'services.moodle.url' => 'https://learn.sits.edu.et']);

    $user = User::factory()->create([
        'is_approved' => true,
        'is_active' => true,
        'password' => Hash::make('ChangeMe@2026'),
        'password_changed' => false,
    ]);

    actingAs($user)->get('/go/lms')->assertRedirect(route('password.force-change'));

    actingAs($user)->post('/password/force-change', [
        'current_password' => 'ChangeMe@2026',
        'password' => 'MyOwnPassword1',
        'password_confirmation' => 'MyOwnPassword1',
    ])->assertRedirect(url('/go/lms'));
});
