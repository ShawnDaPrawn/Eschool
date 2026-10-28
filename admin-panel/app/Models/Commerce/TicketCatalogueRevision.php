<?php

declare(strict_types=1);

namespace App\Models\Commerce;

use App\Casts\UtcInstant;
use App\Services\Platform\Tenancy\BelongsToSchool;
use Illuminate\Database\Eloquent\Model;

class TicketCatalogueRevision extends Model
{
    use BelongsToSchool;

    protected $table = 'ticket_catalogue_revisions';

    protected $guarded = ['id', 'school_id'];

    protected $hidden = ['private_reason'];

    public $timestamps = false;

    protected function casts(): array
    {
        return ['record_id' => 'integer', 'revision' => 'integer', 'actor_id' => 'integer', 'snapshot' => 'array', 'private_reason' => 'encrypted', 'created_at' => UtcInstant::class];
    }
}
