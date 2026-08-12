<?php

namespace Database\Factories;

use App\Models\InventorySupplier;
use Illuminate\Database\Eloquent\Factories\Factory;

/** @extends Factory<InventorySupplier> */
class InventorySupplierFactory extends Factory
{
    protected $model = InventorySupplier::class;

    public function definition(): array
    {
        static $seq = 0;
        $seq++;

        return [
            'code' => 'SUP-T'.str_pad((string) $seq, 4, '0', STR_PAD_LEFT),
            'name' => fake()->unique()->company(),
            'phone' => fake()->numerify('+2519########'),
            'city' => 'Addis Ababa',
            'is_active' => true,
        ];
    }

    public function inactive(): static
    {
        return $this->state(fn () => ['is_active' => false]);
    }
}
