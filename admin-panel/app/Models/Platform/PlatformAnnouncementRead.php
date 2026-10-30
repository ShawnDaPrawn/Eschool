<?php

declare(strict_types=1);

namespace App\Models\Platform;

use App\Services\Platform\Tenancy\BelongsToSchool;
use Illuminate\Database\Eloquent\Model;

/**
 * A school Super Admin dismissed a Navigate Ed announcement's banner (SA.13).
 * Each person sees only their own; HQ sees counts.
 * engineering/specs/platform/hq-announcements.md.
 */
class PlatformAnnouncementRead extends Model
{
    use BelongsToSchool;

    public $timestamps = false;

    protected $table = 'platform_announcement_reads';

    protected $guarded = ['id', 'school_id'];

    protected function casts(): array
    {
        return ['announcement_id' => 'integer', 'user_id' => 'integer', 'read_at' => 'datetime'];
    }
}
