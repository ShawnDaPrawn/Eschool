<?php

declare(strict_types=1);

namespace App\Models\Platform;

use App\Services\Platform\Tenancy\BelongsToSchool;
use Illuminate\Database\Eloquent\Model;

/** HQ record (engineering/specs/platform/hq-school-accounts.md); written only from /saas. */
class SchoolContract extends Model
{
    use BelongsToSchool;

    protected $table = 'school_contracts';

    protected $guarded = ['id', 'school_id'];

    protected function casts(): array
    {
        return ['starts_on' => 'date', 'ends_on' => 'date', 'renewal_on' => 'date', 'operator_agreement_signed_on' => 'date', 'data_processing_signed_on' => 'date', 'contracted_learners' => 'integer', 'price_per_learner_cents' => 'integer'];
    }
}
