<?php

namespace App\Http\Resources;

use App\Models\Booking;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * @mixin Booking
 */
class BookingResource extends JsonResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'status' => $this->status->value,
            'guest_name' => $this->guest_name,
            'guest_email' => $this->guest_email,
            'check_in' => $this->check_in->toDateString(),
            'check_out' => $this->check_out->toDateString(),
            'nights' => $this->nights(),
            'total_cents' => $this->total_cents,
            'room' => RoomResource::make($this->whenLoaded('room')),
            'created_at' => $this->created_at?->toIso8601String(),
        ];
    }
}
