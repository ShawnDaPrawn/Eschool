<?php

declare(strict_types=1);

namespace Tests\Feature\Platform;

use App\Casts\UtcInstant;
use Carbon\CarbonImmutable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use PHPUnit\Framework\Attributes\DataProvider;
use Tests\TestCase;

class UtcInstantTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();
        Schema::dropIfExists('utc_instant_test');
        Schema::create('utc_instant_test', function (Blueprint $t) {
            $t->id();
            $t->dateTime('instant')->nullable();
        });
        $this->beforeApplicationDestroyed(fn () => Schema::dropIfExists('utc_instant_test'));
    }

    public static function zones(): array
    {
        return [['Africa/Johannesburg'], ['America/New_York'], ['UTC']];
    }

    #[DataProvider('zones')]
    public function test_database_roundtrip_preserves_the_same_utc_instant_under_each_school_timezone(string $timezone): void
    {
        config(['app.timezone' => $timezone]);
        $previous = date_default_timezone_get();
        date_default_timezone_set($timezone);
        try {
            $model = new UtcInstantRecord;
            $model->instant = CarbonImmutable::parse('2026-02-10T10:00:00+02:00');
            $model->save();
            $this->assertSame('2026-02-10 08:00:00', DB::table('utc_instant_test')->value('instant'));
            $saved = UtcInstantRecord::findOrFail($model->id);
            $this->assertSame('2026-02-10T08:00:00.000000Z', $saved->instant->toISOString());
            $this->assertSame(0, $saved->instant->offset);
            $this->assertSame('2026-02-10T08:00:00.000000Z', $saved->toArray()['instant']);
            $saved->instant = null;
            $saved->save();
            $this->assertNull($saved->fresh()->instant);
            $this->assertNull($saved->fresh()->toArray()['instant']);
        } finally {
            date_default_timezone_set($previous);
        }
    }

    public function test_explicit_offset_and_naive_utc_values_are_stable_without_mutating_caller_dates(): void
    {
        $cast = new UtcInstant;
        $model = new UtcInstantRecord;
        $input = CarbonImmutable::parse('2026-10-25T23:30:00-04:00');
        $this->assertSame('2026-10-26 03:30:00', $cast->set($model, 'instant', $input, []));
        $this->assertSame(-14400, $input->offset);
        $this->assertSame('2026-10-26T03:30:00.000000Z', $cast->get($model, 'instant', '2026-10-26 03:30:00', [])->toISOString());
    }
}

class UtcInstantRecord extends Model
{
    protected $table = 'utc_instant_test';

    protected $guarded = [];

    public $timestamps = false;

    protected function casts(): array
    {
        return ['instant' => UtcInstant::class];
    }
}
