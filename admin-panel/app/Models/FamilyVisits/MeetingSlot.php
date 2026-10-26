<?php

declare(strict_types=1);

namespace App\Models\FamilyVisits;

use App\Casts\UtcInstant;
use App\Services\Platform\Tenancy\BelongsToSchool;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;

class MeetingSlot extends Model
{
    use BelongsToSchool, HasUuids;

    protected $table = 'meeting_slots';

    protected $guarded = ['id', 'school_id'];

    protected $hidden = ['title', 'venue'];

    protected function casts(): array
    {
        return ['title' => 'encrypted', 'venue' => 'encrypted', 'starts_at' => UtcInstant::class, 'ends_at' => UtcInstant::class, 'booking_closes_at' => UtcInstant::class, 'revision' => 'integer'];
    }
}
