<?php

declare(strict_types=1);

namespace App\Models\Consultations;

use App\Services\Platform\Tenancy\BelongsToSchool;
use Illuminate\Database\Eloquent\Model;

class Response extends Model
{
    use BelongsToSchool;

    protected $table = 'consultation_responses';

    public $timestamps = false;

    public $incrementing = false;

    protected $keyType = 'string';

    protected $guarded = ['school_id'];

    protected function casts(): array
    {
        return ['answers' => 'encrypted:array'];
    }

    protected static function booted(): void
    {
        static::creating(function (self $row) {
            $consultation = Consultation::findOrFail($row->consultation_id);
            abort_unless($consultation->anonymous ? $row->respondent_user_id === null : $row->respondent_user_id !== null, 422, 'Anonymous answers must remain separate from identity.');
        });
        static::updating(fn () => abort(409, 'Recorded responses are immutable.'));
        static::deleting(fn () => abort(409, 'Recorded responses are retained.'));
    }
}
