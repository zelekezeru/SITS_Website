<?php

namespace Database\Factories;

use App\Models\Course;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Course>
 */
class CourseFactory extends Factory
{
    public function definition(): array
    {
        return [
            'title' => fake()->sentence(3),
            'description' => fake()->paragraph(),
            'category' => fake()->word(),
            'credit_hours' => fake()->numberBetween(1, 6),
            'amount_paid' => fake()->randomFloat(2, 100, 2000),
            'instructor' => fake()->name(),
        ];
    }
}
