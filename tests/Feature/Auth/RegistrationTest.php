<?php

use App\Models\User;
use Spatie\Permission\Models\Role;

test('registration screen can be rendered', function () {
    $response = $this->get('/register');

    $response->assertStatus(200);
});

test('new users can register', function () {
    // The merged registration flow requires an explicit role and provisions an
    // approved, active account. It does NOT auto-login; the first/bootstrap user
    // is forwarded to the login screen to sign in.
    Role::create(['name' => 'USER', 'guard_name' => 'web']);

    $response = $this->post('/register', [
        'name' => 'Test User',
        'email' => 'test@example.com',
        'password' => 'password',
        'password_confirmation' => 'password',
        'role' => 'USER',
    ]);

    $response->assertSessionHasNoErrors();
    $response->assertRedirect(route('login', absolute: false));

    $user = User::where('email', 'test@example.com')->first();
    expect($user)->not->toBeNull();
    expect($user->hasRole('USER'))->toBeTrue();
    expect((bool) $user->is_approved)->toBeTrue();
});
