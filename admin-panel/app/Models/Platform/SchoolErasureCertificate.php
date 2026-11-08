<?php

declare(strict_types=1);

namespace App\Models\Platform;

use App\Services\Platform\Tenancy\BelongsToSchool;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Carbon;

/**
 * The record that a leaving school's data was erased (Action Plan SA.11):
 * what was erased and kept (counts per table), when, and the ids of the audit
 * entries for each approval. It names no person and holds no personal
 * information. Written once by SchoolErasure; never changed.
 * engineering/specs/platform/offboarding.md.
 *
 * @property int $id
 * @property int $school_id
 * @property int $offboarding_id
 * @property string $reference
 * @property array<string, mixed> $certificate
 * @property string $certificate_sha256
 * @property Carbon $issued_at
 */
class SchoolErasureCertificate extends Model
{
    use BelongsToSchool;

    protected $table = 'school_erasure_certificates';

    protected $guarded = ['id', 'school_id'];

    protected function casts(): array
    {
        return ['certificate' => 'array', 'issued_at' => 'datetime'];
    }
}
