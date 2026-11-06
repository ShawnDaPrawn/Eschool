<?php

declare(strict_types=1);

namespace App\Models\OpenStandards;

use App\Services\Platform\Tenancy\BelongsToSchool;
use Illuminate\Database\Eloquent\Model;

/** A OneRoster 1.2 CSV export the school asked for (Action Plan 5.9). */
class OneRosterExport extends Model
{
    use BelongsToSchool;

    public const PREPARING = 'preparing';

    public const READY = 'ready';

    public const FAILED = 'failed';

    protected $table = 'oneroster_exports';

    protected $guarded = ['id', 'school_id'];

    protected $hidden = ['reason', 'artifact_path'];

    protected function casts(): array
    {
        return ['reason' => 'encrypted', 'include_demographics' => 'boolean', 'row_counts' => 'array', 'artifact_bytes' => 'integer',
            'download_count' => 'integer', 'ready_at' => 'immutable_datetime', 'expires_at' => 'immutable_datetime', 'last_downloaded_at' => 'immutable_datetime'];
    }
}
