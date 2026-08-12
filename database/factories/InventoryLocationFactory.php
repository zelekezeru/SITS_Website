<?php

namespace Database\Factories;

use App\Enums\InventoryLocationType;
use App\Models\InventoryLocation;
use Illuminate\Database\Eloquent\Factories\Factory;

/** @extends Factory<InventoryLocation> */
class InventoryLocationFactory extends Factory
{
    protected $model = InventoryLocation::class;

    public function definition(): array
    {
        static $seq = 0;
        $seq++;

        return [
            'code' => 'LOC-T-'.str_pad((string) $seq, 4, '0', STR_PAD_LEFT),
            'name' => fake()->unique()->streetName(),
            'type' => InventoryLocationType::Store,
            'is_issuable' => true,
            'is_active' => true,
        ];
    }

    public function of(InventoryLocationType $type): static
    {
        return $this->state(fn () => ['type' => $type]);
    }

    public function childOf(InventoryLocation $parent): static
    {
        return $this->state(fn () => [
            'parent_id' => $parent->id,
            'campus_id' => $parent->campus_id,
        ]);
    }
}
