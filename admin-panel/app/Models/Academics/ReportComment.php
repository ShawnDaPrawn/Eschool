<?php

declare(strict_types=1);

namespace App\Models\Academics;

use App\Services\Platform\Tenancy\BelongsToSchool;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ReportComment extends Model
{
    public function card(): BelongsTo
    {
        return $this->belongsTo(ReportCard::class, 'report_card_id');
    }

    use BelongsToSchool;

    protected $guarded = ['id', 'school_id'];

    protected $hidden = ['payload', 'pdf_path', 'text'];

    protected static function booted(): void
    {
        $guard = function (ReportComment $comment) {
            $ids = array_filter([$comment->report_card_id, $comment->getRawOriginal('report_card_id')]);
            abort_if(ReportCard::whereIn('id', $ids)->whereHas('batch', fn ($q) => $q->where('state', 'released'))->exists(), 409, __('Published report comments are immutable. Prepare a new version.'));
        };
        static::saving($guard);
        static::deleting($guard);
    }

    protected function casts(): array
    {
        return ['text' => 'encrypted', 'revision' => 'integer'];
    }
}
