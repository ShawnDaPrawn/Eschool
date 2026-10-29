<?php

declare(strict_types=1);

namespace App\Models\Money;

use App\Casts\UtcInstant;
use App\Services\Platform\Tenancy\BelongsToSchool;
use Illuminate\Database\Eloquent\Model;

/**
 * @property int $policy_id
 * @property int $revision
 * @property array<string,mixed> $snapshot
 * @property string $private_reason
 */
class FeeLatePolicyRevision extends Model
{
    use BelongsToSchool;

    protected $guarded = ['id', 'school_id'];

    public $timestamps = false;

    protected function casts(): array
    {
        return ['policy_id' => 'integer', 'revision' => 'integer', 'actor_id' => 'integer', 'snapshot' => 'array', 'private_reason' => 'encrypted', 'created_at' => UtcInstant::class];
    }
}
