<?php

namespace App\Http\Controllers\Api;

use App\Actions\CancelBooking;
use App\Actions\CreateBooking;
use App\Http\Controllers\Controller;
use App\Http\Requests\StoreBookingRequest;
use App\Http\Resources\BookingResource;
use App\Models\Booking;
use Illuminate\Http\JsonResponse;

class BookingController extends Controller
{
    public function store(StoreBookingRequest $request, CreateBooking $createBooking): JsonResponse
    {
        $booking = $createBooking->handle(
            roomId: (int) $request->validated('room_id'),
            guestName: $request->validated('guest_name'),
            guestEmail: $request->validated('guest_email'),
            checkIn: $request->checkIn(),
            checkOut: $request->checkOut(),
        );

        return BookingResource::make($booking->load('room.hotel'))
            ->response()
            ->setStatusCode(201);
    }

    public function show(Booking $booking): BookingResource
    {
        return BookingResource::make($booking->load('room.hotel'));
    }

    public function cancel(Booking $booking, CancelBooking $cancelBooking): BookingResource
    {
        return BookingResource::make($cancelBooking->handle($booking)->load('room.hotel'));
    }
}
