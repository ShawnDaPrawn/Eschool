<?php

declare(strict_types=1);

namespace App\Models\Admissions;

use App\Casts\UtcInstant;
use App\Services\Platform\Tenancy\BelongsToSchool;
use Illuminate\Database\Eloquent\Model;

class Intake extends Model
{
    use BelongsToSchool;

    protected $table = 'intakes';

    protected $guarded = ['id', 'school_id'];

    protected $hidden = ['answers', 'parent_contact', 'token_hash', 'path', 'details', 'receipt'];

    protected function casts(): array
    {
        return ['opens_at' => UtcInstant::class, 'closes_at' => UtcInstant::class, 'required_documents' => 'array', 'field_ids' => 'array', 'restricted_field_ids' => 'array', 'revision' => 'integer', 'application_fee_cents' => 'integer', 'keep_for_days' => 'integer'];
    }
}
