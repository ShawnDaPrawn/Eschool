<?php

declare(strict_types=1);

namespace App\Models\Wellbeing;

use App\Services\Platform\Tenancy\BelongsToSchool;
use Illuminate\Database\Eloquent\Model;

class IncidentPhoto extends Model
{
    use BelongsToSchool;

    protected $table = 'incident_photos';

    public $incrementing = false;

    protected $keyType = 'string';

    protected $guarded = ['school_id'];

    protected $hidden = ['path', 'source_digest', 'sha256', 'thumbnail', 'scan_receipt'];

    protected function casts(): array
    {
        return ['thumbnail' => 'encrypted', 'scan_receipt' => 'encrypted:array', 'report_revision' => 'integer', 'bytes' => 'integer', 'width' => 'integer', 'height' => 'integer'];
    }
}
