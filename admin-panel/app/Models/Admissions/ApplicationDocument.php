<?php

declare(strict_types=1);

namespace App\Models\Admissions;

use App\Casts\UtcInstant;
use App\Services\Platform\Tenancy\BelongsToSchool;
use Illuminate\Database\Eloquent\Model;

class ApplicationDocument extends Model
{
    use BelongsToSchool;

    protected $table = 'application_documents';

    protected $guarded = ['id', 'school_id'];

    protected $hidden = ['answers', 'parent_contact', 'token_hash', 'path', 'details', 'receipt'];

    protected function casts(): array
    {
        return ['scanned_at' => UtcInstant::class];
    }
}
