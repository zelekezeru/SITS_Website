<?php

namespace Database\Factories;

use App\Enums\InventoryItemStatus;
use App\Enums\InventoryTrackingMode;
use App\Enums\UnitOfMeasure;
use App\Models\InventoryCategory;
use App\Models\InventoryItem;
use Illuminate\Database\Eloquent\Factories\Factory;

/** @extends Factory<InventoryItem> */
class InventoryItemFactory extends Factory
{
    protected $model = InventoryItem::class;

    public function definition(): array
    {
        static $seq = 0;
        $seq++;

        return [
            'code' => 'ITM-'.str_pad((string) $seq, 5, '0', STR_PAD_LEFT),
            'category_id' => InventoryCategory::factory(),
            'name_en' => fake()->unique()->words(3, true),
            'tracking_mode' => InventoryTrackingMode::Consumable,
            'unit_of_measure' => UnitOfMeasure::Piece,
            'status' => InventoryItemStatus::Active,
            'reorder_level' => 0,
        ];
    }

    public function asset(): static
    {
        return $this->state(fn () => [
            'tracking_mode' => InventoryTrackingMode::Asset,
            'unit_of_measure' => UnitOfMeasure::Piece,
        ]);
    }

    public function withReorderLevel(float $level): static
    {
        return $this->state(fn () => ['reorder_level' => $level]);
    }

    public function discontinued(): static
    {
        return $this->state(fn () => ['status' => InventoryItemStatus::Discontinued]);
    }
}
