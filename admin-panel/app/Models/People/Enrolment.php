<?php

declare(strict_types=1);

namespace App\Models\People;

use App\Models\ClassSection;
use App\Models\Students;
use App\Services\Platform\Audit\Auditable;
use App\Services\Platform\Tenancy\BelongsToSchool;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;

class Enrolment extends Model
{
    use Auditable, BelongsToSchool, SoftDeletes;

    protected $guarded = ['id'];

    protected function casts(): array
    {
        return ['starts_on' => 'date', 'ends_on' => 'date', 'legacy_dates' => 'boolean'];
    }

    public function scopeOnDate(Builder $query, string $date): Builder
    {
        return $query->whereDate('starts_on', '<=', $date)->where(fn ($q) => $q->whereNull('ends_on')->orWhereDate('ends_on', '>=', $date));
    }

    public function learner(): BelongsTo
    {
        return $this->belongsTo(Students::class, 'learner_id');
    }

    public function homeroom(): BelongsTo
    {
        return $this->belongsTo(ClassSection::class, 'class_section_id');
    }
}
