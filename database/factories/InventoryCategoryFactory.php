<?php

namespace Database\Factories;

use App\Enums\DepreciationMethod;
use App\Enums\InventoryTrackingMode;
use App\Models\InventoryCategory;
use Illuminate\Database\Eloquent\Factories\Factory;

/** @extends Factory<InventoryCategory> */
class InventoryCategoryFactory extends Factory
{
    protected $model = InventoryCategory::class;

    public function definition(): array
    {
        static $seq = 0;
        $seq++;

        return [
            'code' => 'C'.str_pad((string) $seq, 3, '0', STR_PAD_LEFT),
            'name_en' => fake()->unique()->words(2, true),
            'tracking_mode' => InventoryTrackingMode::Consumable,
            'default_depreciation_method' => DepreciationMethod::None,
            'is_active' => true,
        ];
    }

    /** A category whose items are serialized assets with a depreciation policy. */
    public function assets(): static
    {
        return $this->state(fn () => [
            'tracking_mode' => InventoryTrackingMode::Asset,
            'default_depreciation_method' => DepreciationMethod::StraightLine,
            'default_useful_life_months' => 48,
        ]);
    }

    public function childOf(InventoryCategory $parent): static
    {
        return $this->state(fn () => ['parent_id' => $parent->id]);
    }
}
