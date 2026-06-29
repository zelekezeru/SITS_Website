<?php

namespace App\Enums;

use App\Enums\Concerns\HasLabel;

enum StrategicPillar: string
{
    use HasLabel;

    case ProgramDelivery = 'program_delivery';
    case Enrollment = 'enrollment';
    case Finance = 'finance';
    case OrganizationalCapacity = 'organizational_capacity';
    case Governance = 'governance';
}
