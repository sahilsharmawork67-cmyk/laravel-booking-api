<?php

namespace App\Models;

use Carbon\CarbonInterface;
use Database\Factories\RoomFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\Scope;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

#[Fillable(['hotel_id', 'name', 'capacity', 'nightly_price_cents'])]
class Room extends Model
{
    /** @use HasFactory<RoomFactory> */
    use HasFactory;

    protected function casts(): array
    {
        return [
            'capacity' => 'integer',
            'nightly_price_cents' => 'integer',
        ];
    }

    /**
     * @return BelongsTo<Hotel, $this>
     */
    public function hotel(): BelongsTo
    {
        return $this->belongsTo(Hotel::class);
    }

    /**
     * @return HasMany<Booking, $this>
     */
    public function bookings(): HasMany
    {
        return $this->hasMany(Booking::class);
    }

    /**
     * Rooms with no active booking overlapping [checkIn, checkOut).
     */
    #[Scope]
    protected function availableBetween(Builder $query, CarbonInterface $checkIn, CarbonInterface $checkOut): void
    {
        $query->whereDoesntHave(
            'bookings',
            fn (Builder $bookings) => $bookings->active()->overlapping($checkIn, $checkOut),
        );
    }
}
