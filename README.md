# Laravel Booking API

A small Laravel REST API for hotel room bookings that guarantees a room can never be double-booked, even under concurrent requests.

## Features

- List hotels and rooms, with an availability filter for a date range
- Create, view and cancel bookings
- Overlap protection using half-open date ranges inside a locked database transaction
- Price computed server-side (nights x nightly rate, stored in integer cents)
- Form Request validation and API Resource responses
- Feature tests covering every overlap case, plus GitHub Actions CI on PHP 8.3 and 8.4

**Stack:** PHP 8.3+, Laravel 13, SQLite (any Laravel-supported database works), PHPUnit 12, Laravel Pint.

## Design note: preventing double bookings

**Half-open ranges.** A stay is treated as `[check_in, check_out)`: the guest occupies the nights starting on `check_in` up to, but not including, `check_out`. Two stays overlap when `existing.check_in < new.check_out AND existing.check_out > new.check_in`. This means a guest can check in on the same day the previous guest checks out, without special-case code. Cancelled bookings are excluded from the check.

**Row lock in a transaction.** A plain "check, then insert" has a race: two requests can both see the room as free and both insert. [`CreateBooking`](app/Actions/CreateBooking.php) opens a transaction and first runs `SELECT ... FOR UPDATE` (`lockForUpdate()`) on the **room** row. A second request for the same room blocks on that lock until the first commits, then its overlap check sees the new booking and returns `409 Conflict`. The room row is locked, not the existing bookings, because when a room has no bookings yet there are no rows to lock. Requests for different rooms do not wait on each other.

On MySQL and PostgreSQL this is a real row lock. SQLite ignores `FOR UPDATE`, but it allows only one writer at a time, so a racing request fails with a "database is locked" error instead of double-booking. That is fine for local development; use MySQL or PostgreSQL in production to get a clean `409`.

Dates are stored as plain `Y-m-d` strings via a small [`DateOnly`](app/Casts/DateOnly.php) cast, so the overlap comparison behaves the same on every database driver.

## API

All endpoints are under `/api` and return JSON. Money is in integer cents. Dates use `YYYY-MM-DD`.

| Method | Endpoint | Description |
| --- | --- | --- |
| `GET` | `/api/hotels` | List hotels with room counts (paginated) |
| `GET` | `/api/rooms` | List rooms. Optional `check_in` and `check_out` (both or neither) return only rooms free for that range. Optional `hotel_id` |
| `POST` | `/api/bookings` | Create a booking. `201` on success, `409` if the dates are taken, `422` on validation errors |
| `GET` | `/api/bookings/{id}` | Show a booking |
| `POST` | `/api/bookings/{id}/cancel` | Cancel a booking. `409` if already cancelled |

### Examples

```bash
# Rooms free from 12 to 14 June (two nights)
curl "http://localhost:8000/api/rooms?check_in=2030-06-12&check_out=2030-06-14"

# Create a booking
curl -X POST http://localhost:8000/api/bookings \
  -H "Accept: application/json" \
  -H "Content-Type: application/json" \
  -d '{
        "room_id": 1,
        "guest_name": "Sam Sample",
        "guest_email": "sam@example.com",
        "check_in": "2030-06-12",
        "check_out": "2030-06-14"
      }'

# Show and cancel it
curl http://localhost:8000/api/bookings/1
curl -X POST http://localhost:8000/api/bookings/1/cancel -H "Accept: application/json"
```

Example response for `POST /api/bookings`:

```json
{
  "data": {
    "id": 1,
    "status": "confirmed",
    "guest_name": "Sam Sample",
    "guest_email": "sam@example.com",
    "check_in": "2030-06-12",
    "check_out": "2030-06-14",
    "nights": 2,
    "total_cents": 24000,
    "room": {
      "id": 1,
      "name": "Standard Double",
      "capacity": 2,
      "nightly_price_cents": 12000,
      "hotel": { "id": 1, "name": "Seaside Inn", "city": "Port Harbor" }
    },
    "created_at": "2030-06-01T09:00:00+00:00"
  }
}
```

A conflicting booking returns:

```json
{ "message": "The room is already booked for some or all of the requested dates." }
```

## Setup

Requires PHP 8.3+ with the `pdo_sqlite` extension, and Composer.

```bash
git clone <repository-url> laravel-booking-api
cd laravel-booking-api
composer install
cp .env.example .env
php artisan key:generate
touch database/database.sqlite
php artisan migrate --seed
php artisan serve
```

The seeder creates three fictional hotels (Seaside Inn, Maple Lodge, Hilltop Suites) with three rooms each, plus one sample booking.

## Running tests

```bash
php artisan test
```

Tests run against an in-memory SQLite database and freeze the clock where dates matter. To check code style:

```bash
vendor/bin/pint --test
```

## Out of scope

This is a focused demo, so a few things a production service would need are intentionally left out: authentication (anyone who knows a booking ID can view or cancel it, so real use would need auth or unguessable booking references), payments, rate limiting, and guest-count vs room-capacity checks.

## License

[MIT](LICENSE)
