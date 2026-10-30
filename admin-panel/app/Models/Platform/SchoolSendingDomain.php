<?php

declare(strict_types=1);

namespace App\Models\Platform;

use App\Services\Platform\Tenancy\BelongsToSchool;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Carbon;

/**
 * The domain a school sends its notices from (Action Plan 2.2). Written only
 * by SendingDomains. engineering/specs/platform/sending-domains.md.
 *
 * @property int $id
 * @property int $school_id
 * @property string $domain
 * @property string $ses_identity
 * @property list<string>|null $dkim_tokens
 * @property string $mail_from_domain
 * @property string $status
 * @property string|null $dkim_status
 * @property string|null $mail_from_status
 * @property Carbon|null $verified_at
 * @property Carbon|null $last_checked_at
 * @property string|null $last_error
 * @property int|null $connected_by
 */
class SchoolSendingDomain extends Model
{
    use BelongsToSchool;

    public const PENDING = 'pending';

    public const VERIFIED = 'verified';

    public const FAILED = 'failed';

    protected $table = 'school_sending_domains';

    protected $guarded = ['id', 'school_id'];

    protected function casts(): array
    {
        return [
            'dkim_tokens' => 'array',
            'verified_at' => 'datetime',
            'last_checked_at' => 'datetime',
        ];
    }
}
