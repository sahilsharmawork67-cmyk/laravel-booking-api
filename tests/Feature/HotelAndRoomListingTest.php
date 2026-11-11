<?php

namespace Tests\Feature;

use App\Models\Booking;
use App\Models\Hotel;
use App\Models\Room;
use Illuminate\Foundation\Testing\RefreshDatabase;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class HotelAndRoomListingTest extends TestCase
{
    use RefreshDatabase;

    #[Test]
    public function it_lists_hotels_with_room_counts(): void
    {
        Hotel::factory()->has(Room::factory()->count(3))->create(['name' => 'Seaside Inn']);
        Hotel::factory()->create(['name' => 'Maple Lodge']);

        $this->getJson('/api/hotels')
            ->assertOk()
            ->assertJsonCount(2, 'data')
            ->assertJsonPath('data.0.name', 'Maple Lodge')
            ->assertJsonPath('data.0.rooms_count', 0)
            ->assertJsonPath('data.1.name', 'Seaside Inn')
            ->assertJsonPath('data.1.rooms_count', 3);
    }

    #[Test]
    public function it_lists_all_rooms_when_no_dates_are_given(): void
    {
        Room::factory()->count(3)->create();

        $this->getJson('/api/rooms')
            ->assertOk()
            ->assertJsonCount(3, 'data')
            ->assertJsonStructure(['data' => [['id', 'name', 'capacity', 'nightly_price_cents', 'hotel' => ['id', 'name']]], 'meta', 'links']);
    }

    #[Test]
    public function it_only_lists_rooms_available_for_the_requested_dates(): void
    {
        [$booked, $backToBack, $cancelled, $free] = Room::factory()->count(4)->create();

        Booking::factory()->for($booked)->create(['check_in' => '2030-06-10', 'check_out' => '2030-06-15']);
        Booking::factory()->for($backToBack)->create(['check_in' => '2030-06-05', 'check_out' => '2030-06-12']);
        Booking::factory()->for($cancelled)->cancelled()->create(['check_in' => '2030-06-12', 'check_out' => '2030-06-14']);

        $response = $this->getJson('/api/rooms?check_in=2030-06-12&check_out=2030-06-14')->assertOk();

        $this->assertEqualsCanonicalizing(
            [$backToBack->id, $cancelled->id, $free->id],
            collect($response->json('data'))->pluck('id')->all(),
        );
    }

    #[Test]
    public function it_filters_rooms_by_hotel(): void
    {
        $hotel = Hotel::factory()->has(Room::factory()->count(2))->create();
        Room::factory()->create();

        $this->getJson("/api/rooms?hotel_id={$hotel->id}")
            ->assertOk()
            ->assertJsonCount(2, 'data');
    }

    #[Test]
    public function it_validates_the_availability_query(): void
    {
        $this->getJson('/api/rooms?check_in=2030-06-12')
            ->assertUnprocessable()
            ->assertJsonValidationErrors('check_out');

        $this->getJson('/api/rooms?check_in=2030-06-12&check_out=2030-06-12')
            ->assertUnprocessable()
            ->assertJsonValidationErrors('check_out');

        $this->getJson('/api/rooms?check_in=junk&check_out=2030-06-12')
            ->assertUnprocessable()
            ->assertJsonValidationErrors('check_in');
    }
}
