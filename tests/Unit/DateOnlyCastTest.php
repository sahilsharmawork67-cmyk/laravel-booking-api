<?php

namespace Tests\Unit;

use App\Casts\DateOnly;
use Carbon\CarbonImmutable;
use Illuminate\Database\Eloquent\Model;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;

class DateOnlyCastTest extends TestCase
{
    #[Test]
    public function it_stores_dates_without_a_time_component(): void
    {
        $cast = new DateOnly;
        $model = new class extends Model {};

        $this->assertSame('2030-06-10', $cast->set($model, 'check_in', CarbonImmutable::parse('2030-06-10 17:45:00'), []));
        $this->assertSame('2030-06-10', $cast->set($model, 'check_in', '2030-06-10', []));
        $this->assertNull($cast->set($model, 'check_in', null, []));
    }

    #[Test]
    public function it_reads_dates_as_immutable_start_of_day(): void
    {
        $value = (new DateOnly)->get(new class extends Model {}, 'check_in', '2030-06-10', []);

        $this->assertInstanceOf(CarbonImmutable::class, $value);
        $this->assertSame('2030-06-10 00:00:00', $value->toDateTimeString());
    }
}
