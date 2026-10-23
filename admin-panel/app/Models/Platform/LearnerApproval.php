<?php

declare(strict_types=1);

namespace App\Models\Platform;

use App\Services\Platform\Audit\Auditable;
use App\Services\Platform\Tenancy\BelongsToSchool;
use Illuminate\Database\Eloquent\Model;

class LearnerApproval extends Model
{
    use Auditable, BelongsToSchool;

    protected $guarded = ['id'];

    protected function casts(): array
    {
        return ['consented_at' => 'datetime', 'withdrawn_at' => 'datetime'];
    }
}
