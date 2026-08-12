<?php

namespace Database\Factories;

use App\Enums\InventoryMovementType;
use App\Models\InventoryItem;
use App\Models\InventoryLocation;
use App\Models\InventoryStockMovement;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<InventoryStockMovement>
 *
 * Quantities are signed by the movement type, so a factory-made ledger sums to
 * the same answer the service layer would produce.
 */
class InventoryStockMovementFactory extends Factory
{
    protected $model = InventoryStockMovement::class;

    public function definition(): array
    {
        return [
            'item_id' => InventoryItem::factory(),
            'type' => InventoryMovementType::Receipt,
            'quantity' => 10,
            'occurred_at' => now(),
        ];
    }

    /** Stock in: +$quantity, landing at $location. */
    public function receipt(float $quantity, ?InventoryLocation $location = null): static
    {
        return $this->state(fn () => [
            'type' => InventoryMovementType::Receipt,
            'quantity' => abs($quantity),
            'to_location_id' => $location?->id,
        ]);
    }

    /** Stock out: −$quantity, leaving $location. */
    public function issue(float $quantity, ?InventoryLocation $location = null): static
    {
        return $this->state(fn () => [
            'type' => InventoryMovementType::Issue,
            'quantity' => -abs($quantity),
            'from_location_id' => $location?->id,
        ]);
    }

    public function adjustment(float $signedQuantity, string $reason = 'Stocktake variance'): static
    {
        return $this->state(fn () => [
            'type' => InventoryMovementType::Adjustment,
            'quantity' => $signedQuantity,
            'reason' => $reason,
        ]);
    }
}
