<?php

namespace Tests\Feature;

use App\Enums\BookingStatus;
use App\Models\Booking;
use Illuminate\Foundation\Testing\RefreshDatabase;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class BookingLifecycleTest extends TestCase
{
    use RefreshDatabase;

    #[Test]
    public function it_shows_a_booking_with_its_room_and_hotel(): void
    {
        $booking = Booking::factory()->create([
            'check_in' => '2030-06-10',
            'check_out' => '2030-06-12',
            'total_cents' => 20_000,
        ]);

        $this->getJson("/api/bookings/{$booking->id}")
            ->assertOk()
            ->assertJsonPath('data.id', $booking->id)
            ->assertJsonPath('data.nights', 2)
            ->assertJsonPath('data.total_cents', 20_000)
            ->assertJsonPath('data.room.id', $booking->room_id)
            ->assertJsonPath('data.room.hotel.id', $booking->room->hotel_id);
    }

    #[Test]
    public function it_returns_404_for_an_unknown_booking(): void
    {
        $this->getJson('/api/bookings/999999')->assertNotFound();
        $this->postJson('/api/bookings/999999/cancel')->assertNotFound();
    }

    #[Test]
    public function it_cancels_a_booking(): void
    {
        $booking = Booking::factory()->create();

        $this->postJson("/api/bookings/{$booking->id}/cancel")
            ->assertOk()
            ->assertJsonPath('data.status', 'cancelled');

        $this->assertSame(BookingStatus::Cancelled, $booking->fresh()->status);
    }

    #[Test]
    public function it_refuses_to_cancel_a_booking_twice(): void
    {
        $booking = Booking::factory()->cancelled()->create();

        $this->postJson("/api/bookings/{$booking->id}/cancel")
            ->assertStatus(409)
            ->assertJsonPath('message', 'This booking has already been cancelled.');
    }
}
