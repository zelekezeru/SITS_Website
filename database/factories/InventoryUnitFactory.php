<?php

namespace Database\Factories;

use App\Enums\InventoryCondition;
use App\Enums\InventoryUnitStatus;
use App\Models\Employee;
use App\Models\InventoryItem;
use App\Models\InventoryUnit;
use Illuminate\Database\Eloquent\Factories\Factory;

/** @extends Factory<InventoryUnit> */
class InventoryUnitFactory extends Factory
{
    protected $model = InventoryUnit::class;

    public function definition(): array
    {
        static $seq = 0;
        $seq++;

        return [
            'item_id' => InventoryItem::factory()->asset(),
            'asset_tag' => 'SITS-T-'.str_pad((string) $seq, 6, '0', STR_PAD_LEFT),
            'serial_number' => strtoupper(fake()->bothify('??####??##')),
            'status' => InventoryUnitStatus::InStore,
            'condition' => InventoryCondition::New,
            'salvage_value' => 0,
        ];
    }

    public function issuedTo(Employee $employee): static
    {
        return $this->state(fn () => [
            'status' => InventoryUnitStatus::Issued,
            'assigned_to_employee_id' => $employee->id,
            'assigned_at' => now(),
        ]);
    }

    public function disposed(): static
    {
        return $this->state(fn () => ['status' => InventoryUnitStatus::Disposed]);
    }

    /** A depreciating asset placed in service $months ago. */
    public function depreciating(float $cost, int $usefulLifeMonths, int $monthsAgo): static
    {
        return $this->state(fn () => [
            'purchase_cost' => $cost,
            'depreciation_method' => \App\Enums\DepreciationMethod::StraightLine,
            'useful_life_months' => $usefulLifeMonths,
            'in_service_on' => now()->subMonths($monthsAgo)->toDateString(),
        ]);
    }
}
