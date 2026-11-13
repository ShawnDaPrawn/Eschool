<?php

declare(strict_types=1);

namespace App\Models;

use App\Services\Platform\Tenancy\BelongsToSchool;
use Illuminate\Database\Eloquent\Model;

class EventReadinessLink extends Model
{
    use BelongsToSchool;

    protected $guarded = ['id', 'school_id'];

    protected $casts = ['revision' => 'integer', 'event_id' => 'integer', 'ticket_event_id' => 'integer', 'transport_notes' => 'encrypted'];
}
