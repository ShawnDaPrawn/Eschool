<?php

declare(strict_types=1);

namespace App\Models\Admissions;

use App\Services\Platform\Tenancy\BelongsToSchool;
use Illuminate\Database\Eloquent\Model;

class ApplicationEvent extends Model
{
    use BelongsToSchool;

    protected $table = 'application_events';

    protected $guarded = ['id', 'school_id'];

    protected $hidden = ['answers', 'parent_contact', 'token_hash', 'path', 'details', 'receipt'];

    protected function casts(): array
    {
        return ['details' => 'encrypted:array'];
    }
}
