<?php

declare(strict_types=1);

namespace App\Models\Admissions;

use App\Services\Platform\Tenancy\BelongsToSchool;
use Illuminate\Database\Eloquent\Model;

class AdmissionOperation extends Model
{
    use BelongsToSchool;

    protected $table = 'admission_operations';

    protected $guarded = ['id', 'school_id'];

    protected $hidden = ['answers', 'parent_contact', 'token_hash', 'path', 'details', 'receipt'];

    protected function casts(): array
    {
        return ['receipt' => 'encrypted:array'];
    }
}
