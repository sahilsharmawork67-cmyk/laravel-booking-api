<?php

namespace App\Actions;

use App\Enums\BookingStatus;
use App\Exceptions\RoomUnavailableException;
use App\Models\Booking;
use App\Models\Room;
use Carbon\CarbonImmutable;
use Illuminate\Support\Facades\DB;

class CreateBooking
{
    /**
     * Create a confirmed booking, guaranteeing no overlap with another active
     * booking for the same room.
     *
     * The room row is locked (SELECT ... FOR UPDATE) for the duration of the
     * transaction, so concurrent requests for the same room are serialised:
     * the second request waits, then sees the first booking in its overlap
     * check. Locking the parent room (rather than existing bookings) also
     * covers the case where there are no overlapping rows yet to lock.
     *
     * @throws RoomUnavailableException
     */
    public function handle(
        int $roomId,
        string $guestName,
        string $guestEmail,
        CarbonImmutable $checkIn,
        CarbonImmutable $checkOut,
    ): Booking {
        return DB::transaction(function () use ($roomId, $guestName, $guestEmail, $checkIn, $checkOut) {
            $room = Room::query()->lockForUpdate()->findOrFail($roomId);

            $conflict = $room->bookings()
                ->active()
                ->overlapping($checkIn, $checkOut)
                ->exists();

            if ($conflict) {
                throw new RoomUnavailableException;
            }

            $nights = (int) $checkIn->diffInDays($checkOut);

            return $room->bookings()->create([
                'guest_name' => $guestName,
                'guest_email' => $guestEmail,
                'check_in' => $checkIn,
                'check_out' => $checkOut,
                'status' => BookingStatus::Confirmed,
                'total_cents' => $nights * $room->nightly_price_cents,
            ]);
        });
    }
}
