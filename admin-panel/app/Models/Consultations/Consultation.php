<?php

declare(strict_types=1);

namespace App\Models\Consultations;

use App\Casts\UtcInstant;
use App\Services\Platform\Tenancy\BelongsToSchool;
use Illuminate\Database\Eloquent\Model;

class Consultation extends Model
{
    use BelongsToSchool;

    protected $table = 'consultations';

    protected $guarded = ['id', 'school_id'];

    protected static function booted(): void
    {
        static::updating(function (self $row) {
            if ($row->getRawOriginal('status') !== 'draft') {
                foreach (['type', 'session_year_id', 'title', 'instructions', 'audience', 'anonymous', 'results_visibility', 'opens_at', 'closes_at', 'created_by'] as $field) {
                    abort_if($row->isDirty($field), 409, 'Published consultation configuration is retained. Create a new consultation.');
                }
            }
        });
        static::deleting(fn () => abort(409, 'Consultation history is retained.'));
    }

    protected function casts(): array
    {
        return ['audience' => 'encrypted:array', 'title' => 'encrypted', 'instructions' => 'encrypted', 'anonymous' => 'boolean', 'opens_at' => UtcInstant::class, 'closes_at' => UtcInstant::class, 'revision' => 'integer', 'ended_at' => UtcInstant::class];
    }
}
