<?php

namespace App\Models;

use App\Casts\DateOnly;
use App\Enums\BookingStatus;
use Carbon\CarbonInterface;
use Database\Factories\BookingFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\Scope;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

#[Fillable(['room_id', 'guest_name', 'guest_email', 'check_in', 'check_out', 'status', 'total_cents'])]
class Booking extends Model
{
    /** @use HasFactory<BookingFactory> */
    use HasFactory;

    protected function casts(): array
    {
        return [
            'check_in' => DateOnly::class,
            'check_out' => DateOnly::class,
            'status' => BookingStatus::class,
            'total_cents' => 'integer',
        ];
    }

    /**
     * @return BelongsTo<Room, $this>
     */
    public function room(): BelongsTo
    {
        return $this->belongsTo(Room::class);
    }

    public function nights(): int
    {
        return (int) $this->check_in->diffInDays($this->check_out);
    }

    /**
     * Bookings that still hold their dates (i.e. not cancelled).
     */
    #[Scope]
    protected function active(Builder $query): void
    {
        $query->where('status', BookingStatus::Confirmed);
    }

    /**
     * Half-open interval overlap: [a, b) and [c, d) overlap iff a < d AND c < b.
     * A stay checking out on day X does not overlap one checking in on day X.
     */
    #[Scope]
    protected function overlapping(Builder $query, CarbonInterface $checkIn, CarbonInterface $checkOut): void
    {
        $query->where('check_in', '<', $checkOut->toDateString())
            ->where('check_out', '>', $checkIn->toDateString());
    }
}
