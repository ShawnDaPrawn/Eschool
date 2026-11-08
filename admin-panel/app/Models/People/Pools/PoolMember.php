<?php

declare(strict_types=1);

namespace App\Models\People\Pools;

use App\Services\People\Pools\PoolAccess;
use App\Services\Platform\Tenancy\BelongsToSchool;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Carbon;

/**
 * One person in a school's alumni and volunteer pool (Action Plan 5.6).
 *
 * @property int $id
 * @property int $user_id
 * @property string $kind
 * @property int|null $learner_id
 * @property string $consent_status
 * @property string $vetting_status
 * @property Carbon|null $vetting_expires_on
 * @property bool $offers_career_talk
 * @property bool $offers_job_shadow
 * @property int $revision
 */
class PoolMember extends Model
{
    use BelongsToSchool;

    public const ALUMNUS = 'alumnus';

    public const VOLUNTEER = 'volunteer';

    public const INVITED = 'invited';

    public const GIVEN = 'given';

    public const WITHDRAWN = 'withdrawn';

    public const PENDING = 'pending';

    public const CLEARED = 'cleared';

    protected $table = 'people_pool_members';

    protected $guarded = ['id', 'school_id'];

    protected function casts(): array
    {
        return ['offers_career_talk' => 'boolean', 'offers_job_shadow' => 'boolean', 'consented_at' => 'datetime', 'withdrawn_at' => 'datetime',
            'vetted_on' => 'date', 'vetting_expires_on' => 'date', 'revision' => 'integer'];
    }

    /**
     * Consent given to the current wording. After the consent text changes,
     * an earlier consent no longer counts: the person signs in and accepts
     * again before they can be booked or use the pool.
     */
    public function consentCurrent(): bool
    {
        return $this->consent_status === self::GIVEN && hash_equals(PoolAccess::consentVersion(), (string) $this->consent_version);
    }

    /** Cleared by the school, and the clearance still runs on $date. */
    public function vettedOn(string $date): bool
    {
        return $this->vetting_status === self::CLEARED && $this->vetting_expires_on !== null && $this->vetting_expires_on->toDateString() >= $date;
    }

    public function offers(string $kind): bool
    {
        return match ($kind) {
            'career_talk' => $this->offers_career_talk,
            'job_shadow' => $this->offers_job_shadow,
            default => false,
        };
    }
}
