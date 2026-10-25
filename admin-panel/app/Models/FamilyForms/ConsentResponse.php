<?php

declare(strict_types=1);

namespace App\Models\FamilyForms;

use App\Casts\UtcInstant;
use App\Services\Platform\Tenancy\BelongsToSchool;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;

class ConsentResponse extends Model
{
    use BelongsToSchool, HasUuids;

    protected $table = 'consent_responses';

    protected $guarded = ['id', 'school_id'];

    protected $hidden = ['signed_name', 'signature'];

    protected function casts(): array
    {
        return ['signed_name' => 'encrypted', 'signed_at' => UtcInstant::class, 'revision' => 'integer'];
    }
}
