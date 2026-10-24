<?php

declare(strict_types=1);

namespace App\Models\Academics;

use App\Services\Platform\Tenancy\BelongsToSchool;
use Illuminate\Database\Eloquent\Model;

class AcademicExitPackage extends Model
{
    use BelongsToSchool;

    protected $guarded = ['id', 'school_id'];

    protected $hidden = ['blueprint', 'manifest', 'artifact_path', 'request_hash'];

    protected function casts(): array
    {
        return ['blueprint' => 'encrypted:array', 'manifest' => 'encrypted:array', 'artifact_bytes' => 'integer', 'expires_at' => 'datetime', 'generated_at' => 'datetime'];
    }
}
