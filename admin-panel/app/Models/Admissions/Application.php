<?php

declare(strict_types=1);

namespace App\Models\Admissions;

use App\Casts\UtcInstant;
use App\Services\Platform\Tenancy\BelongsToSchool;
use Illuminate\Database\Eloquent\Model;

class Application extends Model
{
    use BelongsToSchool;

    protected $table = 'applications';

    protected $guarded = ['id', 'school_id'];

    protected $hidden = ['answers', 'parent_contact', 'token_hash', 'path', 'details', 'receipt', 'draft_key_hash', 'creation_hash', 'email_hash'];

    protected function casts(): array
    {
        return ['answers' => 'encrypted:array', 'parent_contact' => 'encrypted:array', 'submitted_at' => UtcInstant::class, 'email_verified_at' => UtcInstant::class, 'waitlisted_at' => UtcInstant::class, 'terminal_at' => UtcInstant::class, 'enrolled_at' => UtcInstant::class, 'revision' => 'integer', 'office_fee_cents' => 'integer', 'office_fee_paid' => 'boolean'];
    }
}
