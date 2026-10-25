<?php

declare(strict_types=1);

namespace App\Models\Consultations;

use App\Services\Platform\Tenancy\BelongsToSchool;
use Illuminate\Database\Eloquent\Model;

class Option extends Model
{
    use BelongsToSchool;

    protected $table = 'consultation_options';

    protected $guarded = ['id', 'school_id'];

    protected function casts(): array
    {
        return ['label' => 'encrypted'];
    }

    protected static function booted(): void
    {
        $guard = static function (self $row) {
            abort_unless(Consultation::findOrFail($row->consultation_id)->status === 'draft', 409, 'Published consultation questions and candidates are immutable.');
        };
        static::creating($guard);
        static::updating($guard);
        static::deleting($guard);
    }
}
