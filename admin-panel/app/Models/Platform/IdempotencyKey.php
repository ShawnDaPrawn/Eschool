<?php

declare(strict_types=1);

namespace App\Models\Platform;

use App\Models\User;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Prunable;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Carbon;

/**
 * A write sent with an Idempotency-Key and the response it got, for 24 hours.
 * Read and written only by IdempotencyService.
 *
 * @property int $id
 * @property int $user_id
 * @property string $key
 * @property string $key_hash
 * @property string $method
 * @property string $route
 * @property string $request_hash
 * @property int|null $response_status
 * @property array<string, string>|null $response_headers
 * @property string|null $response_body
 * @property Carbon $expires_at
 * @property Carbon $created_at
 */
class IdempotencyKey extends Model
{
    use Prunable;

    protected $fillable = [
        'user_id', 'key', 'key_hash', 'method', 'route', 'request_hash',
        'response_status', 'response_headers', 'response_body', 'expires_at',
    ];

    protected function casts(): array
    {
        return [
            'response_status' => 'integer',
            'response_headers' => 'array',
            'expires_at' => 'datetime',
        ];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /** Expired keys, deleted by `php artisan model:prune` (daily, routes/console.php). */
    public function prunable(): Builder
    {
        return static::query()->where('expires_at', '<', now());
    }
}
