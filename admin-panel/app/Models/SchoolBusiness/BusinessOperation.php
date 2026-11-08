<?php

declare(strict_types=1);

namespace App\Models\SchoolBusiness;

use App\Casts\UtcInstant;
use App\Services\Platform\Tenancy\BelongsToSchool;
use Illuminate\Database\Eloquent\Model;

/**
 * Action Plan 5.10; engineering/specs/money/school-business-tools.md.
 *
 * @property int $actor_id
 * @property string $operation_id
 * @property string $kind
 * @property string $request_hash
 * @property array<string, mixed> $receipt
 */
class BusinessOperation extends Model
{
    use BelongsToSchool;

    protected $table = 'school_business_operations';

    protected $guarded = ['id', 'school_id'];

    public $timestamps = false;

    protected function casts(): array
    {
        return ['receipt' => 'array', 'actor_id' => 'integer', 'created_at' => UtcInstant::class];
    }
}
