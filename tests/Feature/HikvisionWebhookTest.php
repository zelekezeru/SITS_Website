<?php

use App\Models\AttendanceLog;

beforeEach(function () {
    config()->set('services.hikvision.webhook_secret', 'test-secret');
    config()->set('services.hikvision.allowed_ips', []);
    config()->set('services.hikvision.major_event_types', ['5']);
    config()->set('services.hikvision.granted_sub_event_types', []);
    config()->set('services.hikvision.dedup_seconds', 60);
});

/**
 * Build a HikVision AccessControllerEvent JSON payload, with overrides merged
 * recursively so a test can tweak a single field.
 */
function hikPayload(array $overrides = []): array
{
    return array_replace_recursive([
        'dateTime' => '2026-07-22T08:30:00+03:00',
        'AccessControllerEvent' => [
            'employeeNoString' => 'EMP001',
            'deviceName' => 'Main Gate',
            'majorEventType' => 5,
            'subEventType' => 75, // face auth passed
            'attendanceStatus' => 'checkIn',
        ],
    ], $overrides);
}

it('rejects a punch that presents no token', function () {
    $this->postJson('/hikvision/webhook', hikPayload())->assertStatus(401);

    expect(AttendanceLog::count())->toBe(0);
});

it('logs a valid access-granted punch when the token is correct', function () {
    $this->postJson('/hikvision/webhook?token=test-secret', hikPayload())->assertOk();

    expect(AttendanceLog::count())->toBe(1);

    $log = AttendanceLog::first();
    expect($log->device_employee_code)->toBe('EMP001');
    expect($log->direction)->toBe('in');
});

it('accepts the token via the X-Webhook-Token header', function () {
    $this->postJson('/hikvision/webhook', hikPayload(), ['X-Webhook-Token' => 'test-secret'])->assertOk();

    expect(AttendanceLog::count())->toBe(1);
});

it('ignores events with no employee number (exit button / heartbeat / failed auth)', function () {
    $this->postJson('/hikvision/webhook?token=test-secret', hikPayload([
        'AccessControllerEvent' => ['employeeNoString' => ''],
    ]))->assertOk();

    expect(AttendanceLog::count())->toBe(0);
});

it('ignores a non-access major event type (alarm/tamper)', function () {
    $this->postJson('/hikvision/webhook?token=test-secret', hikPayload([
        'AccessControllerEvent' => ['majorEventType' => 1],
    ]))->assertOk();

    expect(AttendanceLog::count())->toBe(0);
});

it('de-duplicates rapid repeat scans for a single entry (face then card)', function () {
    $url = '/hikvision/webhook?token=test-secret';

    $this->postJson($url, hikPayload(['AccessControllerEvent' => ['subEventType' => 75]]))->assertOk(); // face
    $this->postJson($url, hikPayload(['AccessControllerEvent' => ['subEventType' => 1]]))->assertOk();  // card, same second

    expect(AttendanceLog::count())->toBe(1);
});
