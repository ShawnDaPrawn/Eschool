<?php

declare(strict_types=1);

namespace App\Models\Recognition;

use App\Services\Platform\Tenancy\BelongsToSchool;
use Illuminate\Database\Eloquent\Model;

class RecognitionSettings extends Model
{
    use BelongsToSchool;

    protected $table = 'recognition_settings';

    protected $guarded = ['id', 'school_id'];

    protected function casts(): array
    {
        return ['daily_staff_cap' => 'integer', 'public_points_allowed' => 'boolean', 'principal_approval_required' => 'boolean', 'revision' => 'integer'];
    }
}
