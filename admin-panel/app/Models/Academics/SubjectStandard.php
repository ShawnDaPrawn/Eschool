<?php

declare(strict_types=1);

namespace App\Models\Academics;

use Illuminate\Database\Eloquent\Model;

/** Global, versioned public curriculum reference; only the trusted operator importer writes. */
class SubjectStandard extends Model
{
    protected $guarded = ['id'];

    protected function casts(): array
    {
        return ['grade_from' => 'integer', 'grade_to' => 'integer', 'component_weights' => 'array',
            'effective_on' => 'date', 'published_at' => 'datetime'];
    }
}
