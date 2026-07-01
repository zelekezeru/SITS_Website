<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Employee;
use App\Models\AttendanceLog;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Carbon\Carbon;

class HikvisionWebhookController extends Controller
{
    /**
     * Receives event alerts from HikVision Access Control Devices (HTTP Listening).
     */
    public function handle(Request $request)
    {
        $rawContent = $request->getContent();
        
        Log::info('HikVision Webhook Received:', [
            'content_type' => $request->header('Content-Type'),
            'ip' => $request->ip(),
        ]);

        $employeeNo = null;
        $deviceName = null;
        $dateTimeStr = null;
        $attendanceStatus = null;
        $payload = null;

        // 1. Try parsing as JSON
        if ($request->isJson() || str_contains($request->header('Content-Type', ''), 'application/json')) {
            $data = $request->json()->all();
            $payload = $data;
            
            $event = $data['AccessControllerEvent'] ?? [];
            $employeeNo = $event['employeeNoString'] ?? $event['employeeNo'] ?? null;
            $deviceName = $event['deviceName'] ?? $data['deviceName'] ?? null;
            $dateTimeStr = $data['dateTime'] ?? null;
            $attendanceStatus = $event['attendanceStatus'] ?? null;
        } 
        // 2. Fallback to XML parsing
        else {
            try {
                // Disable entity loader for security
                $xml = simplexml_load_string($rawContent, 'SimpleXMLElement', LIBXML_NOCDATA);
                if ($xml) {
                    $payload = json_decode(json_encode($xml), true);
                    
                    $event = $xml->AccessControllerEvent ?? null;
                    if ($event) {
                        $employeeNo = (string) ($event->employeeNoString ?? $event->employeeNo ?? '');
                        $deviceName = (string) ($event->deviceName ?? '');
                        $attendanceStatus = (string) ($event->attendanceStatus ?? '');
                    }
                    if (!$deviceName) {
                        $deviceName = (string) ($xml->deviceName ?? '');
                    }
                    $dateTimeStr = (string) ($xml->dateTime ?? '');
                }
            } catch (\Exception $e) {
                Log::error('HikVision Webhook: Failed parsing XML payload', ['error' => $e->getMessage()]);
                return response()->json(['message' => 'Invalid payload format'], 400);
            }
        }

        if (empty($employeeNo)) {
            Log::warning('HikVision Webhook: Received event with no employee number.');
            return response()->json(['message' => 'Employee number not found in event'], 400);
        }

        // Find the system employee matching the device code
        $employee = Employee::where('device_employee_code', $employeeNo)->first();

        // Parse date time
        $swipeTime = $dateTimeStr ? Carbon::parse($dateTimeStr) : now();

        // Create log record
        $log = AttendanceLog::create([
            'employee_id' => $employee?->id,
            'device_employee_code' => $employeeNo,
            'device_name' => $deviceName,
            'swipe_time' => $swipeTime,
            'direction' => $this->normalizeDirection($attendanceStatus),
            'raw_payload' => $payload,
        ]);

        Log::info("HikVision Webhook: Logged swipe for employee code {$employeeNo}.", [
            'matched_employee' => $employee?->full_name_en,
            'log_id' => $log->id,
        ]);

        return response()->json([
            'message' => 'Event logged successfully',
            'log_id' => $log->id,
        ], 200);
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
}
