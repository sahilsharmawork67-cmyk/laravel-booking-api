<?php

namespace App\Actions;

use App\Enums\BookingStatus;
use App\Exceptions\BookingAlreadyCancelledException;
use App\Models\Booking;
use Illuminate\Support\Facades\DB;

class CancelBooking
{
    /**
     * @throws BookingAlreadyCancelledException
     */
    public function handle(Booking $booking): Booking
    {
        return DB::transaction(function () use ($booking) {
            $booking = Booking::query()->lockForUpdate()->findOrFail($booking->getKey());

            if ($booking->status === BookingStatus::Cancelled) {
                throw new BookingAlreadyCancelledException;
            }

            $booking->update(['status' => BookingStatus::Cancelled]);

            return $booking;
        });
    }
}
