<?php

namespace App\Enums;

use App\Enums\Concerns\HasLabel;

enum ConductIssueType: string
{
    use HasLabel;

    case Misconduct = 'misconduct';
    case AttendanceViolation = 'attendance_violation';
    case PerformanceIssue = 'performance_issue';
    case PolicyViolation = 'policy_violation';
    case Insubordination = 'insubordination';
    case OtherConcern = 'other_concern';

    public function label(): string
    {
        return match ($this) {
            self::Misconduct => 'Misconduct',
            self::AttendanceViolation => 'Attendance Violation',
            self::PerformanceIssue => 'Performance Issue',
            self::PolicyViolation => 'Policy Violation',
            self::Insubordination => 'Insubordination',
            self::OtherConcern => 'Other Concern',
        };
    }

    public function amharicLabel(): string
    {
        return match ($this) {
            self::Misconduct => 'ስህተት መግባት',
            self::AttendanceViolation => 'የአገልግሎት ምክንያት አለማግኘት',
            self::PerformanceIssue => 'የስራ ብቃት ችግር',
            self::PolicyViolation => 'የተቋማዊ ሕግ ጥሰት',
            self::Insubordination => 'አለስ ታዛዥነት',
            self::OtherConcern => 'ሌላ ችግር',
        };
    }
}
