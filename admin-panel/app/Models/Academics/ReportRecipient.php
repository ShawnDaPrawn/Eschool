<?php

declare(strict_types=1);

namespace App\Models\Academics;

use App\Services\Platform\Tenancy\BelongsToSchool;
use Illuminate\Database\Eloquent\Model;

class ReportRecipient extends Model
{
    use BelongsToSchool;

    protected $guarded = ['id', 'school_id'];

    protected $hidden = ['payload', 'pdf_path', 'text'];

    protected function casts(): array
    {
        return ['read_at' => 'datetime'];
    }
}
