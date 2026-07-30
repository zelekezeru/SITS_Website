<?php

namespace Database\Factories;

use App\Models\IntegrityAuditLog;
use App\Models\IntegrityReport;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<IntegrityAuditLog>
 */
class IntegrityAuditLogFactory extends Factory
{
    public function definition(): array
    {
        return [
            'report_id' => IntegrityReport::factory(),
            'user_id' => User::factory(),
            'action' => 'analyze',
            'meta' => [],
        ];
    }
}
