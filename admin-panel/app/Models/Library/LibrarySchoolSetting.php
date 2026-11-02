<?php

declare(strict_types=1);

namespace App\Models\Library;

use App\Services\Platform\Tenancy\BelongsToSchool;
use Illuminate\Database\Eloquent\Model;

/** One school's switch for reading the platform library (off by default). School-owned. */
class LibrarySchoolSetting extends Model
{
    use BelongsToSchool;

    protected $table = 'library_school_settings';

    protected $guarded = ['id', 'school_id'];

    protected function casts(): array
    {
        return ['enabled' => 'boolean'];
    }
}
