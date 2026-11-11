<?php

namespace Database\Factories;

use App\Enums\BookingStatus;
use App\Models\Booking;
use App\Models\Room;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Booking>
 */
class BookingFactory extends Factory
{
    public function definition(): array
    {
        $checkIn = now()->addDays(fake()->numberBetween(1, 60))->startOfDay();
        $nights = fake()->numberBetween(1, 5);

        return [
            'room_id' => Room::factory(),
            'guest_name' => fake()->name(),
            'guest_email' => fake()->safeEmail(),
            'check_in' => $checkIn,
            'check_out' => $checkIn->copy()->addDays($nights),
            'status' => BookingStatus::Confirmed,
            'total_cents' => $nights * 10000,
        ];
    }

    public function cancelled(): static
    {
        return $this->state(['status' => BookingStatus::Cancelled]);
    }
}
