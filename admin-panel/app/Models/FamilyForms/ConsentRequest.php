<?php

declare(strict_types=1);

namespace App\Models\FamilyForms;

use App\Casts\UtcInstant;
use App\Services\Platform\Tenancy\BelongsToSchool;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;

class ConsentRequest extends Model
{
    use BelongsToSchool, HasUuids;

    protected $table = 'consent_requests';

    protected $guarded = ['id', 'school_id'];

    protected $hidden = ['title', 'wording'];

    protected function casts(): array
    {
        return ['title' => 'encrypted', 'wording' => 'encrypted', 'event_starts_at' => UtcInstant::class, 'event_ends_at' => UtcInstant::class, 'respond_by' => UtcInstant::class, 'cancelled_at' => UtcInstant::class, 'revision' => 'integer'];
    }
}
