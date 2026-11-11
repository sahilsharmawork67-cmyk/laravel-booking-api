<?php

namespace Tests\Feature;

use App\Enums\BookingStatus;
use App\Models\Booking;
use App\Models\Room;
use Illuminate\Foundation\Testing\RefreshDatabase;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class CreateBookingTest extends TestCase
{
    use RefreshDatabase;

    private Room $room;

    protected function setUp(): void
    {
        parent::setUp();

        $this->travelTo('2030-06-01 09:00:00');
        $this->room = Room::factory()->create(['nightly_price_cents' => 12_500]);
    }

    #[Test]
    public function it_creates_a_booking_and_computes_the_total_server_side(): void
    {
        $response = $this->postJson('/api/bookings', $this->payload([
            'check_in' => '2030-06-10',
            'check_out' => '2030-06-13',
            'total_cents' => 1, // ignored: the client never sets the price
        ]));

        $response->assertCreated()
            ->assertJsonPath('data.status', 'confirmed')
            ->assertJsonPath('data.check_in', '2030-06-10')
            ->assertJsonPath('data.check_out', '2030-06-13')
            ->assertJsonPath('data.nights', 3)
            ->assertJsonPath('data.total_cents', 37_500)
            ->assertJsonPath('data.room.id', $this->room->id);

        $this->assertDatabaseHas('bookings', [
            'room_id' => $this->room->id,
            'guest_email' => 'sam@example.com',
            'check_in' => '2030-06-10',
            'check_out' => '2030-06-13',
            'status' => 'confirmed',
            'total_cents' => 37_500,
        ]);
    }

    /**
     * Existing confirmed booking: 2030-06-10 -> 2030-06-15.
     *
     * @return array<string, array{string, string}>
     */
    public static function overlappingRanges(): array
    {
        return [
            'identical range' => ['2030-06-10', '2030-06-15'],
            'overlaps the start' => ['2030-06-08', '2030-06-11'],
            'overlaps the end' => ['2030-06-14', '2030-06-18'],
            'inside the existing stay' => ['2030-06-11', '2030-06-12'],
            'wraps the existing stay' => ['2030-06-05', '2030-06-20'],
        ];
    }

    #[Test]
    #[DataProvider('overlappingRanges')]
    public function it_rejects_overlapping_bookings_for_the_same_room(string $checkIn, string $checkOut): void
    {
        $this->existingBooking('2030-06-10', '2030-06-15');

        $this->postJson('/api/bookings', $this->payload(['check_in' => $checkIn, 'check_out' => $checkOut]))
            ->assertStatus(409)
            ->assertJsonPath('message', 'The room is already booked for some or all of the requested dates.');

        $this->assertDatabaseCount('bookings', 1);
    }

    #[Test]
    public function it_allows_a_booking_that_checks_in_on_the_previous_check_out_day(): void
    {
        $this->existingBooking('2030-06-10', '2030-06-15');

        $this->postJson('/api/bookings', $this->payload(['check_in' => '2030-06-15', 'check_out' => '2030-06-17']))
            ->assertCreated();
    }

    #[Test]
    public function it_allows_a_booking_that_checks_out_on_the_next_check_in_day(): void
    {
        $this->existingBooking('2030-06-10', '2030-06-15');

        $this->postJson('/api/bookings', $this->payload(['check_in' => '2030-06-07', 'check_out' => '2030-06-10']))
            ->assertCreated();
    }

    #[Test]
    public function a_cancelled_booking_does_not_block_its_dates(): void
    {
        $this->existingBooking('2030-06-10', '2030-06-15', BookingStatus::Cancelled);

        $this->postJson('/api/bookings', $this->payload(['check_in' => '2030-06-10', 'check_out' => '2030-06-15']))
            ->assertCreated();
    }

    #[Test]
    public function cancelling_a_booking_frees_the_dates_for_a_new_booking(): void
    {
        $booking = $this->existingBooking('2030-06-10', '2030-06-15');
        $payload = $this->payload(['check_in' => '2030-06-12', 'check_out' => '2030-06-14']);

        $this->postJson('/api/bookings', $payload)->assertStatus(409);
        $this->postJson("/api/bookings/{$booking->id}/cancel")->assertOk();
        $this->postJson('/api/bookings', $payload)->assertCreated();
    }

    #[Test]
    public function bookings_for_a_different_room_do_not_conflict(): void
    {
        Booking::factory()->create([
            'check_in' => '2030-06-10',
            'check_out' => '2030-06-15',
        ]);

        $this->postJson('/api/bookings', $this->payload(['check_in' => '2030-06-10', 'check_out' => '2030-06-15']))
            ->assertCreated();
    }

    /**
     * @return array<string, array{array<string, mixed>, string}>
     */
    public static function invalidPayloads(): array
    {
        return [
            'missing room' => [['room_id' => null], 'room_id'],
            'unknown room' => [['room_id' => 999_999], 'room_id'],
            'missing guest name' => [['guest_name' => ''], 'guest_name'],
            'invalid email' => [['guest_email' => 'not-an-email'], 'guest_email'],
            'bad date format' => [['check_in' => '10/06/2030'], 'check_in'],
            'check-in in the past' => [['check_in' => '2030-05-31', 'check_out' => '2030-06-02'], 'check_in'],
            'check-out equals check-in' => [['check_in' => '2030-06-10', 'check_out' => '2030-06-10'], 'check_out'],
            'check-out before check-in' => [['check_in' => '2030-06-10', 'check_out' => '2030-06-09'], 'check_out'],
        ];
    }

    #[Test]
    #[DataProvider('invalidPayloads')]
    public function it_validates_the_request(array $overrides, string $invalidField): void
    {
        $this->postJson('/api/bookings', $this->payload($overrides))
            ->assertUnprocessable()
            ->assertJsonValidationErrors($invalidField);

        $this->assertDatabaseCount('bookings', 0);
    }

    /**
     * @param  array<string, mixed>  $overrides
     * @return array<string, mixed>
     */
    private function payload(array $overrides = []): array
    {
        return array_merge([
            'room_id' => $this->room->id,
            'guest_name' => 'Sam Sample',
            'guest_email' => 'sam@example.com',
            'check_in' => '2030-06-10',
            'check_out' => '2030-06-12',
        ], $overrides);
    }

    private function existingBooking(string $checkIn, string $checkOut, BookingStatus $status = BookingStatus::Confirmed): Booking
    {
        return Booking::factory()->for($this->room)->create([
            'check_in' => $checkIn,
            'check_out' => $checkOut,
            'status' => $status,
        ]);
    }
}
