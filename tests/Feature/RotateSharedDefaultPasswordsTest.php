<?php

/*
|--------------------------------------------------------------------------
| Retiring the shared default password
|--------------------------------------------------------------------------
| The Moodle importer gave every account it created the same password. This
| covers the command that replaces it with a unique one per account, and the
| importer change that stops it happening again.
*/

use App\Models\User;
use App\Support\OneTimePassword;
use Illuminate\Support\Facades\Hash;

use function Pest\Laravel\artisan;

it('gives every account on the shared default its own password', function () {
    $shared = OneTimePassword::legacySharedDefault();

    $users = collect(range(1, 3))->map(fn ($i) => User::factory()->create([
        'email' => "shared{$i}@sits.edu.et",
        'password' => Hash::make($shared),
        'password_changed' => false,
    ]));

    artisan('users:rotate-shared-defaults')->assertSuccessful();

    $seen = [];

    foreach ($users as $user) {
        $user->refresh();

        // The shared value must no longer open the account.
        expect(Hash::check($shared, $user->password))->toBeFalse();

        // The replacement is stored so an admin can read it back, and it works.
        $issued = $user->readableDefaultPassword();
        expect($issued)->not->toBeNull()
            ->and(Hash::check($issued, $user->password))->toBeTrue();

        // Still forced to change it on first login.
        expect($user->password_changed)->toBeFalse();

        $seen[] = $issued;
    }

    // The whole point: no two accounts share a password.
    expect($seen)->toHaveCount(3)
        ->and(array_unique($seen))->toHaveCount(3);
});

it('leaves alone a password the user actually chose', function () {
    $user = User::factory()->create([
        'password' => Hash::make('AUserChosenPassword1'),
        'password_changed' => true,
    ]);

    artisan('users:rotate-shared-defaults')->assertSuccessful();

    expect(Hash::check('AUserChosenPassword1', $user->fresh()->password))->toBeTrue();
});

it('leaves alone an unchanged password that is not the shared default', function () {
    // An admin-provisioned account with its own one-time password must not be
    // rotated out from under whoever was already told that password.
    $user = User::factory()->create([
        'password' => Hash::make('SomeOtherIssued1'),
        'password_changed' => false,
    ]);

    artisan('users:rotate-shared-defaults')->assertSuccessful();

    expect(Hash::check('SomeOtherIssued1', $user->fresh()->password))->toBeTrue();
});

it('changes nothing on a dry run', function () {
    $shared = OneTimePassword::legacySharedDefault();

    $user = User::factory()->create([
        'password' => Hash::make($shared),
        'password_changed' => false,
    ]);

    artisan('users:rotate-shared-defaults', ['--dry-run' => true])->assertSuccessful();

    expect(Hash::check($shared, $user->fresh()->password))->toBeTrue();
});

it('generates passwords without characters that are misread when dictated', function () {
    $banned = ['0', 'O', '1', 'l', 'I', '5', 'S', '2', 'Z'];

    for ($i = 0; $i < 40; $i++) {
        $password = OneTimePassword::generate();

        expect(strlen($password))->toBe(12);

        foreach ($banned as $char) {
            expect(str_contains($password, $char))->toBeFalse();
        }
    }
});
