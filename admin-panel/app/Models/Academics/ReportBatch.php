<?php

declare(strict_types=1);

namespace App\Models\Academics;

use App\Services\Platform\Tenancy\BelongsToSchool;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class ReportBatch extends Model
{
    use BelongsToSchool;

    protected $guarded = ['id', 'school_id'];

    protected $hidden = ['payload', 'pdf_path', 'text'];

    protected static function booted(): void
    {
        $guard = function (ReportBatch $batch) {
            abort_if($batch->getRawOriginal('state') === 'released', 409, __('Released reports are immutable. Prepare a new version.'));
        };
        static::updating($guard);
        static::deleting($guard);
    }

    protected function casts(): array
    {
        return ['selection' => 'array', 'design' => 'array', 'signature_data' => 'array', 'revision' => 'integer', 'grade_signed_at' => 'datetime', 'principal_signed_at' => 'datetime', 'released_at' => 'datetime'];
    }

    public function cards(): HasMany
    {
        return $this->hasMany(ReportCard::class);
    }
}
