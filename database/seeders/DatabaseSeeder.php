<?php

namespace Database\Seeders;

use App\Models\Booking;
use App\Models\Hotel;
use App\Models\Room;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    use WithoutModelEvents;

    public function run(): void
    {
        $hotels = [
            ['name' => 'Seaside Inn', 'city' => 'Port Harbor'],
            ['name' => 'Maple Lodge', 'city' => 'Pine Valley'],
            ['name' => 'Hilltop Suites', 'city' => 'Riverton'],
        ];

        foreach ($hotels as $attributes) {
            $hotel = Hotel::create($attributes);

            Room::factory()->for($hotel)->createMany([
                ['name' => 'Standard Double', 'capacity' => 2, 'nightly_price_cents' => 12_000],
                ['name' => 'Deluxe King', 'capacity' => 2, 'nightly_price_cents' => 18_500],
                ['name' => 'Family Suite', 'capacity' => 4, 'nightly_price_cents' => 26_000],
            ]);
        }

        $room = Room::query()->first();
        $checkIn = now()->addWeek()->startOfDay();

        Booking::factory()->for($room)->create([
            'guest_name' => 'Jane Example',
            'guest_email' => 'jane@example.com',
            'check_in' => $checkIn,
            'check_out' => $checkIn->copy()->addDays(3),
            'total_cents' => 3 * $room->nightly_price_cents,
        ]);
    }
}
