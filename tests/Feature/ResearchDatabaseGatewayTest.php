<?php

use App\Models\User;

/**
 * The JSTOR and EBSCO gateways are plain redirects behind `auth` — the
 * institutional subscription is what authorises, so every signed-in user may
 * use them. These tests pin that contract, and pin that the shared EBSCO
 * credential is only ever appended server-side.
 */

test('guests cannot reach the research database gateways', function () {
    $this->get('/library/portal')->assertRedirect('/login');
    $this->get('/library/ebsco')->assertRedirect('/login');
});

test('any signed-in user is handed off to jstor', function () {
    config(['services.jstore.url' => 'https://www.jstor.org/']);

    $this->actingAs(User::factory()->create())
        ->get('/library/portal')
        ->assertRedirect('https://www.jstor.org/');
});

test('ebsco hand-off appends the shared credential using authtype uid', function () {
    config([
        'services.ebsco.url' => 'https://search.ebscohost.com/login.aspx',
        'services.ebsco.username' => 'test-institution',
        'services.ebsco.password' => 'test-secret',
        'services.ebsco.profile' => 'ehost',
    ]);

    $target = $this->actingAs(User::factory()->create())
        ->get('/library/ebsco')
        ->assertRedirectContains('search.ebscohost.com/login.aspx')
        ->headers->get('Location');

    expect($target)
        ->toContain('authtype=uid')
        ->toContain('user=test-institution')
        ->toContain('password=test-secret')
        ->toContain('profile=ehost');
});

test('ebsco degrades to a plain redirect when no credential is configured', function () {
    // This is the shape referring-URL or IP authentication would use: no secret
    // in the URL at all. Dropping EBSCO_USERNAME from .env is the whole switch.
    config([
        'services.ebsco.url' => 'https://sits.idm.oclc.org/ebsco',
        'services.ebsco.username' => null,
    ]);

    $this->actingAs(User::factory()->create())
        ->get('/library/ebsco')
        ->assertRedirect('https://sits.idm.oclc.org/ebsco');
});
