<?php

declare(strict_types=1);

namespace App\Models\Academics;

use App\Services\Platform\Tenancy\BelongsToSchool;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class ReportCard extends Model
{
    use BelongsToSchool;

    protected $guarded = ['id', 'school_id'];

    protected $hidden = ['payload', 'pdf_path', 'text'];

    protected static function booted(): void
    {
        $guard = function (ReportCard $card) {
            $ids = array_filter([$card->report_batch_id, $card->getRawOriginal('report_batch_id')]);
            abort_if(ReportBatch::whereIn('id', $ids)->where('state', 'released')->exists(), 409, __('Released report cards are immutable. Prepare a new version.'));
        };
        static::saving($guard);
        static::deleting($guard);
    }

    protected function casts(): array
    {
        return ['payload' => 'encrypted:array', 'generated_at' => 'datetime'];
    }

    public function batch(): BelongsTo
    {
        return $this->belongsTo(ReportBatch::class, 'report_batch_id');
    }

    public function comments(): HasMany
    {
        return $this->hasMany(ReportComment::class);
    }
}
