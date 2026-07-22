<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Employee;
use App\Models\AttendanceLog;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Carbon\Carbon;

class HikvisionWebhookController extends Controller
{
    /**
     * Receives event alerts from HikVision Access Control devices (HTTP
     * Listening / event notification). The device is on the college LAN with
     * outbound internet and POSTs each punch to https://sits.edu.et here.
     *
     * Pipeline: authenticate the device → parse JSON/XML → keep only genuine
     * access-granted punches → de-duplicate rapid repeat scans → log.
     */
    public function handle(Request $request): JsonResponse
    {
        // 1. Authenticate the device before touching the payload.
        if ($rejection = $this->authorizeDevice($request)) {
            return $rejection;
        }

        Log::info('HikVision Webhook Received:', [
            'content_type' => $request->header('Content-Type'),
            'ip' => $request->ip(),
        ]);

        // 2. Normalize the payload (JSON first, XML fallback).
        $event = $this->parsePayload($request);
        if ($event === null) {
            return response()->json(['message' => 'Invalid payload format'], 400);
        }

        // 3. Keep only real access-granted attendance punches. Non-attendance
        //    events (exit button, tamper, heartbeat, failed/anonymous auth,
        //    non-access major types) are acknowledged with 200 but not stored,
        //    so the device does not treat them as delivery failures.
        if ($reason = $this->rejectionReason($event)) {
            Log::info("HikVision Webhook: ignored event ({$reason}).", [
                'employee_no' => $event['employeeNo'],
                'major' => $event['majorEventType'],
                'sub' => $event['subEventType'],
            ]);

            return response()->json(['message' => "Ignored: {$reason}"], 200);
        }

        $swipeTime = $event['dateTime'] ? Carbon::parse($event['dateTime']) : now();
        $direction = $this->normalizeDirection($event['attendanceStatus']);

        // 4. De-duplicate: a single entry often fires more than one event
        //    (face + card on the same reader) or the person taps twice.
        if ($this->isDuplicate($event['employeeNo'], $event['deviceName'], $direction, $swipeTime)) {
            Log::info("HikVision Webhook: duplicate punch ignored for code {$event['employeeNo']}.");

            return response()->json(['message' => 'Duplicate punch ignored'], 200);
        }

        // Match the device code to a system employee (nullable — unmatched
        // codes are still logged so they surface for reconciliation).
        $employee = Employee::where('device_employee_code', $event['employeeNo'])->first();

        $log = AttendanceLog::create([
            'employee_id' => $employee?->id,
            'device_employee_code' => $event['employeeNo'],
            'device_name' => $event['deviceName'],
            'swipe_time' => $swipeTime,
            'direction' => $direction,
            'raw_payload' => $event['payload'],
        ]);

        Log::info("HikVision Webhook: Logged swipe for employee code {$event['employeeNo']}.", [
            'matched_employee' => $employee?->full_name_en,
            'log_id' => $log->id,
        ]);

        return response()->json([
            'message' => 'Event logged successfully',
            'log_id' => $log->id,
        ], 200);
    }

    /**
     * Guard the public endpoint. Returns a rejection response when the request
     * fails the IP allow-list or the shared-secret check, otherwise null.
     */
    private function authorizeDevice(Request $request): ?JsonResponse
    {
        $allowedIps = config('services.hikvision.allowed_ips', []);
        if (!empty($allowedIps) && !in_array($request->ip(), $allowedIps, true)) {
            Log::warning('HikVision Webhook: rejected request from disallowed IP.', ['ip' => $request->ip()]);

            return response()->json(['message' => 'Forbidden'], 403);
        }

        $secret = (string) config('services.hikvision.webhook_secret', '');
        if ($secret === '') {
            Log::warning('HikVision Webhook: HIKVISION_WEBHOOK_SECRET is not set — the attendance endpoint is UNAUTHENTICATED. Set it and configure the device token to secure it.');

            return null; // Allowed for backward-compatibility, but loudly flagged.
        }

        // The device may present the secret as ?token=, an X-Webhook-Token
        // header, or the password half of HTTP Basic auth — whichever its
        // firmware supports.
        $presented = $request->query('token')
            ?? $request->header('X-Webhook-Token')
            ?? $request->getPassword()
            ?? '';

        if (!hash_equals($secret, (string) $presented)) {
            Log::warning('HikVision Webhook: rejected request with invalid or missing token.', ['ip' => $request->ip()]);

            return response()->json(['message' => 'Unauthorized'], 401);
        }

        return null;
    }

    /**
     * Parse the raw request into a normalized event array, or null if the body
     * is not parseable.
     *
     * @return array{employeeNo:?string,deviceName:?string,dateTime:?string,attendanceStatus:?string,majorEventType:?string,subEventType:?string,payload:?array}|null
     */
    private function parsePayload(Request $request): ?array
    {
        $contentType = (string) $request->header('Content-Type', '');

        // 1. Check for JSON payload or JSON/form input inside multipart/form-data
        $data = null;
        if ($request->isJson() || str_contains($contentType, 'application/json')) {
            $data = $request->json()->all();
        } elseif ($request->has('event_log')) {
            $eventLog = $request->input('event_log');
            if (is_string($eventLog)) {
                $decoded = json_decode($eventLog, true);
                $data = is_array($decoded) ? $decoded : null;

                if ($data === null && str_contains($eventLog, '<?xml')) {
                    try {
                        $xml = simplexml_load_string($eventLog, 'SimpleXMLElement', LIBXML_NOCDATA);
                        if ($xml) {
                            $data = json_decode(json_encode($xml), true);
                        }
                    } catch (\Throwable $e) {
                        // proceed to fallbacks
                    }
                }
            } elseif (is_array($eventLog)) {
                $data = $eventLog;
            }
        } elseif (!empty($request->all())) {
            $data = $request->all();
        }

        if (is_array($data) && !empty($data)) {
            $event = $data['AccessControllerEvent'] ?? $data;

            return [
                'employeeNo' => $this->firstNonEmpty(
                    $event['employeeNoString'] ?? null,
                    isset($event['employeeNo']) ? (string) $event['employeeNo'] : null,
                    $data['employeeNoString'] ?? null,
                    isset($data['employeeNo']) ? (string) $data['employeeNo'] : null,
                ),
                'deviceName' => $event['deviceName'] ?? $data['deviceName'] ?? null,
                'dateTime' => $data['dateTime'] ?? $event['dateTime'] ?? null,
                'attendanceStatus' => $event['attendanceStatus'] ?? $data['attendanceStatus'] ?? null,
                'majorEventType' => $this->stringOrNull($event['majorEventType'] ?? $data['majorEventType'] ?? null),
                'subEventType' => $this->stringOrNull($event['subEventType'] ?? $data['subEventType'] ?? null),
                'payload' => $data,
            ];
        }

        // 2. Fallback XML parsing directly from raw content
        try {
            $content = $request->getContent();
            if (!empty($content)) {
                $xml = simplexml_load_string($content, 'SimpleXMLElement', LIBXML_NOCDATA);
                if ($xml) {
                    $payload = json_decode(json_encode($xml), true);
                    $event = $xml->AccessControllerEvent ?? null;

                    return [
                        'employeeNo' => $event
                            ? $this->firstNonEmpty((string) ($event->employeeNoString ?? ''), (string) ($event->employeeNo ?? ''))
                            : null,
                        'deviceName' => $this->firstNonEmpty(
                            $event ? (string) ($event->deviceName ?? '') : null,
                            (string) ($xml->deviceName ?? ''),
                        ),
                        'dateTime' => $this->stringOrNull((string) ($xml->dateTime ?? '')),
                        'attendanceStatus' => $event ? $this->stringOrNull((string) ($event->attendanceStatus ?? '')) : null,
                        'majorEventType' => $event
                            ? $this->stringOrNull((string) ($event->majorEventType ?? ''))
                            : $this->stringOrNull((string) ($xml->majorEventType ?? '')),
                        'subEventType' => $event
                            ? $this->stringOrNull((string) ($event->subEventType ?? ''))
                            : $this->stringOrNull((string) ($xml->subEventType ?? '')),
                        'payload' => $payload,
                    ];
                }
            }
        } catch (\Throwable $e) {
            Log::error('HikVision Webhook: Failed parsing XML payload', ['error' => $e->getMessage()]);
        }

        return null;
    }

    /**
     * Decide whether an event should be dropped rather than stored as
     * attendance. Returns a human-readable reason, or null to keep it.
     */
    private function rejectionReason(array $event): ?string
    {
        // Genuine identified punches always carry a non-zero employee number.
        // Its absence marks exit-button presses, heartbeats and anonymous or
        // failed authentication attempts.
        $employeeNo = $event['employeeNo'];
        if ($employeeNo === null || $employeeNo === '' || $employeeNo === '0') {
            return 'no employee number';
        }

        // Restrict to access-control "event" major types (default 5).
        $majors = config('services.hikvision.major_event_types', []);
        if (!empty($majors) && $event['majorEventType'] !== null
            && !in_array($event['majorEventType'], $majors, true)) {
            return 'non-attendance major event type '.$event['majorEventType'];
        }

        // Optional per-firmware allow-list of "authentication passed" subtypes.
        $grantedSubs = config('services.hikvision.granted_sub_event_types', []);
        if (!empty($grantedSubs) && $event['subEventType'] !== null
            && !in_array($event['subEventType'], $grantedSubs, true)) {
            return 'non-granted sub event type '.$event['subEventType'];
        }

        return null;
    }

    /**
     * True when a matching punch already exists within the dedup window, so
     * this event is a repeat scan (same employee + device + direction).
     */
    private function isDuplicate(string $employeeNo, ?string $deviceName, string $direction, Carbon $swipeTime): bool
    {
        $window = (int) config('services.hikvision.dedup_seconds', 60);
        if ($window <= 0) {
            return false;
        }

        return AttendanceLog::where('device_employee_code', $employeeNo)
            ->where('device_name', $deviceName)
            ->where('direction', $direction)
            ->whereBetween('swipe_time', [
                $swipeTime->copy()->subSeconds($window),
                $swipeTime->copy()->addSeconds($window),
            ])
            ->exists();
    }

    private function normalizeDirection(?string $status): string
    {
        if (empty($status)) {
            return 'unknown';
        }

        $status = strtolower($status);
        if (str_contains($status, 'in') || $status === 'checkin') {
            return 'in';
        }
        if (str_contains($status, 'out') || $status === 'checkout') {
            return 'out';
        }

        return 'unknown';
    }

    /**
     * Return the first argument that is neither null nor an empty/zero string.
     */
    private function firstNonEmpty(?string ...$values): ?string
    {
        foreach ($values as $value) {
            if ($value !== null && $value !== '' && $value !== '0') {
                return $value;
            }
        }

        return null;
    }

    private function stringOrNull(?string $value): ?string
    {
        $value = $value === null ? null : trim($value);

        return ($value === null || $value === '') ? null : $value;
    }
}
