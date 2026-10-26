<?php

declare(strict_types=1);

namespace App\Models\Platform;

use App\Casts\UtcInstant;
use Carbon\CarbonImmutable;
use Illuminate\Database\Eloquent\Model;

/**
 * A domain event in the outbox (domain_events). Written by Outbox::record()
 * and read by the listeners DispatchOutbox hands it to.
 *
 * @property int $id
 * @property int|null $school_id
 * @property string $type
 * @property array<string, mixed> $payload
 * @property CarbonImmutable $occurred_at
 * @property CarbonImmutable|null $available_at
 * @property CarbonImmutable|null $dispatched_at
 * @property int $attempts
 * @property string|null $last_error
 */
class OutboxEvent extends Model
{
    protected $table = 'domain_events';

    public $timestamps = false;

    protected $guarded = ['id'];

    protected $casts = [
        'school_id' => 'integer',
        'payload' => 'array',
        'occurred_at' => UtcInstant::class,
        'available_at' => UtcInstant::class,
        'dispatched_at' => UtcInstant::class,
        'attempts' => 'integer',
    ];
}
