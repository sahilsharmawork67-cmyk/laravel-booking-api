<?php

namespace Database\Factories;

use App\Models\Hotel;
use App\Models\Room;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Room>
 */
class RoomFactory extends Factory
{
    public function definition(): array
    {
        return [
            'hotel_id' => Hotel::factory(),
            'name' => fake()->randomElement(['Standard', 'Deluxe', 'Family', 'Garden View']).' '.fake()->numberBetween(100, 499),
            'capacity' => fake()->numberBetween(1, 4),
            'nightly_price_cents' => fake()->numberBetween(80, 300) * 100,
        ];
    }
}
