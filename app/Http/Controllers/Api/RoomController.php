<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\ListRoomsRequest;
use App\Http\Resources\RoomResource;
use App\Models\Room;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;

class RoomController extends Controller
{
    /**
     * List rooms, optionally only those free for the whole [check_in, check_out) range.
     */
    public function index(ListRoomsRequest $request): AnonymousResourceCollection
    {
        $rooms = Room::query()
            ->with('hotel')
            ->when($request->validated('hotel_id'), fn ($query, $hotelId) => $query->where('hotel_id', $hotelId))
            ->when($request->hasDateRange(), fn ($query) => $query->availableBetween($request->checkIn(), $request->checkOut()))
            ->orderBy('id')
            ->paginate()
            ->withQueryString();

        return RoomResource::collection($rooms);
    }
}
