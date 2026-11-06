<?php

declare(strict_types=1);

namespace App\Models\OpenStandards;

use App\Services\Platform\Tenancy\BelongsToSchool;
use Illuminate\Database\Eloquent\Model;

/**
 * One Open Badges 3.0 credential issued for a recognition award (Action Plan
 * 5.9). The signed document is rebuilt from this row and the award, so it is
 * the same every time it is downloaded; revocation is recorded here.
 */
class OpenBadgeCredential extends Model
{
    use BelongsToSchool;

    protected $table = 'open_badge_credentials';

    protected $guarded = ['id', 'school_id'];

    protected function casts(): array
    {
        return ['award_presentation_id' => 'integer', 'learner_id' => 'integer', 'issued_at' => 'immutable_datetime', 'revoked_at' => 'immutable_datetime'];
    }
}
