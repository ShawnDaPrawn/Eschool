<?php

declare(strict_types=1);

namespace App\Models\Academics;

use App\Services\Platform\Tenancy\BelongsToSchool;
use Illuminate\Database\Eloquent\Model;

class ContentSetting extends Model
{
    use BelongsToSchool;

    protected $guarded = ['id', 'school_id'];

    protected $hidden = [];

    protected function casts(): array
    {
        return ['transcription_enabled' => 'boolean', 'daily_transcription_seconds' => 'integer', 'revision' => 'integer'];
    }
}
