<?php

declare(strict_types=1);

namespace App\Models\PeerTutoring;

use App\Services\Platform\Tenancy\BelongsToSchool;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;

class PeerTutoringSession extends Model
{
    use BelongsToSchool, HasUuids;

    protected $guarded = ['id', 'school_id'];

    protected $hidden = ['correction_reason'];

    protected function casts(): array
    {
        return ['revision' => 'integer', 'verified_minutes' => 'integer', 'starts_at' => 'immutable_datetime', 'ends_at' => 'immutable_datetime', 'correction_reason' => 'encrypted'];
    }
}
