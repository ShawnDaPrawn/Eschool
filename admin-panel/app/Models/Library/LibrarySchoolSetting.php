<?php

declare(strict_types=1);

namespace App\Models\Library;

use App\Services\Platform\Tenancy\BelongsToSchool;
use Illuminate\Database\Eloquent\Model;

/**
 * One school's switch for reading the platform library (off by default, set by
 * HQ), and its own sharing choices (Action Plan 5.4): whether its teachers may
 * share questions with the library (off by default) and whether a school
 * leader approves each one first (on by default). School-owned.
 */
class LibrarySchoolSetting extends Model
{
    use BelongsToSchool;

    protected $table = 'library_school_settings';

    protected $guarded = ['id', 'school_id'];

    protected function casts(): array
    {
        return ['enabled' => 'boolean', 'sharing_enabled' => 'boolean', 'leader_approval' => 'boolean', 'sharing_changed_at' => 'datetime'];
    }
}
