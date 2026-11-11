<?php

namespace Database\Factories;

use App\Models\Hotel;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Hotel>
 */
class HotelFactory extends Factory
{
    public function definition(): array
    {
        return [
            'name' => fake()->randomElement(['Seaside', 'Harbor', 'Maple', 'Hilltop', 'Riverside', 'Old Mill']).' '.fake()->randomElement(['Inn', 'Lodge', 'Hotel', 'Suites']),
            'city' => fake()->city(),
        ];
    }
}
