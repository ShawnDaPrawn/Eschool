<?php

declare(strict_types=1);

namespace App\Models\Platform;

use Illuminate\Database\Eloquent\Model;
use LogicException;

/**
 * One audit_log row. Append-only: written by App\Services\Platform\Audit\Audit
 * and never changed or deleted through the model.
 *
 * @property int $id
 * @property int|null $school_id
 * @property int|null $actor_id
 * @property string $actor_type
 * @property string $action
 * @property string|null $subject_type
 * @property int|null $subject_id
 * @property array<string, mixed>|null $before
 * @property array<string, mixed>|null $after
 * @property string|null $ip
 * @property string|null $request_id
 */
class AuditLog extends Model
{
    protected $table = 'audit_log';

    public const UPDATED_AT = null;

    protected $guarded = ['id'];

    protected $casts = [
        'school_id' => 'integer',
        'actor_id' => 'integer',
        'subject_id' => 'integer',
        'before' => 'array',
        'after' => 'array',
        'created_at' => 'datetime',
    ];

    protected static function booted(): void
    {
        static::updating(fn () => throw new LogicException('audit_log rows cannot be changed.'));
        static::deleting(fn () => throw new LogicException('audit_log rows cannot be deleted.'));
    }
}
