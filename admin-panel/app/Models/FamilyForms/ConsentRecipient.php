<?php

declare(strict_types=1);

namespace App\Models\FamilyForms;

use App\Services\Platform\Tenancy\BelongsToSchool;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;

class ConsentRecipient extends Model
{
    use BelongsToSchool, HasUuids;

    protected $table = 'consent_recipients';

    protected $guarded = ['id', 'school_id'];

    protected $hidden = [];

    protected function casts(): array
    {
        return ['revision' => 'integer'];
    }
}
