<?php

use App\Models\SecureDocument;
use App\Models\User;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\PermissionRegistrar;

function librarianUser(): User
{
    Permission::firstOrCreate(['name' => 'view_secure_pdf', 'guard_name' => 'web']);
    Permission::firstOrCreate(['name' => 'upload_secure_pdf', 'guard_name' => 'web']);
    app(PermissionRegistrar::class)->forgetCachedPermissions();

    $user = User::factory()->create();
    $user->givePermissionTo('view_secure_pdf', 'upload_secure_pdf');

    return $user;
}

it('stores an uploaded PDF larger than the old 50MB cap', function () {
    Storage::fake('archive');
    $user = librarianUser();

    $pdf = UploadedFile::fake()->create('thesis.pdf', 61440, 'application/pdf'); // 60 MB

    $this->actingAs($user)
        ->post(route('library.archive.store'), [
            'title' => 'Big Thesis',
            'pdf' => $pdf,
            'visibility' => 'role_gated',
        ])
        ->assertRedirect(route('library.archive.index'));

    expect(SecureDocument::count())->toBe(1);

    // The redirect above (rather than a validation error) is itself the proof
    // that the old `max:51200` 50MB cap is gone — a 60MB file now passes.
    // (Faked uploads carry only a reported size and no bytes on disk, so real
    // byte size can't be asserted here.)
    $doc = SecureDocument::first();
    expect($doc->disk)->toBe('archive');
    expect($doc->title)->toBe('Big Thesis');
    expect($doc->mime)->toBe('application/pdf');
    expect($doc->sha256)->toHaveLength(64);
    Storage::disk('archive')->assertExists($doc->path);
});

it('rejects a non-PDF upload', function () {
    Storage::fake('archive');
    $user = librarianUser();

    $this->actingAs($user)
        ->post(route('library.archive.store'), [
            'title' => 'Not a PDF',
            'pdf' => UploadedFile::fake()->create('image.png', 100, 'image/png'),
            'visibility' => 'role_gated',
        ])
        ->assertSessionHasErrors('pdf');

    expect(SecureDocument::count())->toBe(0);
});
