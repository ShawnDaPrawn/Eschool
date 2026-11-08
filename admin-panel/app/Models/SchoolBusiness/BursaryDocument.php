<?php

declare(strict_types=1);

namespace App\Models\SchoolBusiness;

use App\Casts\UtcInstant;
use App\Services\Platform\Tenancy\BelongsToSchool;
use Carbon\CarbonImmutable;
use Illuminate\Database\Eloquent\Model;

/**
 * Action Plan 5.10; engineering/specs/money/school-business-tools.md.
 *
 * @property int $id
 * @property int $application_id
 * @property string $handle
 * @property string $operation_id
 * @property string $source_digest
 * @property string $category
 * @property string $path
 * @property string $mime
 * @property int $bytes
 * @property string $sha256
 * @property string $scanner
 * @property CarbonImmutable $scanned_at
 */
class BursaryDocument extends Model
{
    use BelongsToSchool;

    protected $table = 'bursary_documents';

    protected $guarded = ['id', 'school_id'];

    protected $hidden = ['path', 'source_digest'];

    protected function casts(): array
    {
        return ['application_id' => 'integer', 'bytes' => 'integer', 'scanned_at' => UtcInstant::class];
    }
}
