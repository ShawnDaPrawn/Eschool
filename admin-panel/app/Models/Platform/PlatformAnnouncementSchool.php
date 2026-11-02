<?php

declare(strict_types=1);

namespace App\Models\Platform;

use App\Services\Platform\Tenancy\BelongsToSchool;
use Illuminate\Database\Eloquent\Model;

/**
 * A school a Navigate Ed announcement reached (SA.13), fixed at publication.
 * A school reads an announcement only through its own row; HQ writes them.
 * engineering/specs/platform/hq-announcements.md.
 */
class PlatformAnnouncementSchool extends Model
{
    use BelongsToSchool;

    protected $table = 'platform_announcement_schools';

    protected $guarded = ['id', 'school_id'];

    protected function casts(): array
    {
        return ['announcement_id' => 'integer', 'recipients' => 'integer'];
    }
}
