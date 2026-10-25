<?php

declare(strict_types=1);

namespace App\Models\Admissions;

use App\Casts\UtcInstant;
use App\Services\Platform\Tenancy\BelongsToSchool;
use Illuminate\Database\Eloquent\Model;

class Offer extends Model
{
    use BelongsToSchool;

    protected $table = 'offers';

    protected $guarded = ['id', 'school_id'];

    protected $hidden = ['answers', 'parent_contact', 'token_hash', 'path', 'details', 'receipt'];

    protected function casts(): array
    {
        return ['expires_at' => UtcInstant::class, 'accepted_at' => UtcInstant::class, 'revision' => 'integer'];
    }
}
