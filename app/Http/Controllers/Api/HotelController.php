<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Resources\HotelResource;
use App\Models\Hotel;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;

class HotelController extends Controller
{
    public function index(): AnonymousResourceCollection
    {
        $hotels = Hotel::query()
            ->withCount('rooms')
            ->orderBy('name')
            ->paginate();

        return HotelResource::collection($hotels);
    }
}
