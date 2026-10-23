<?php

declare(strict_types=1);

namespace App\Models\Platform;

use App\Services\Platform\Audit\Auditable;
use App\Services\Platform\Tenancy\BelongsToSchool;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class ProfileFieldDefinition extends Model
{
    use Auditable, BelongsToSchool, SoftDeletes;

    protected $guarded = ['id'];

    protected function casts(): array
    {
        return [
            'validation' => 'array',
            'options' => 'array',
            'visible_to' => 'array',
            'editable_by' => 'array',
            'required' => 'boolean',
            'platform_locked' => 'boolean',
            'sensitive' => 'boolean',
            'encrypted' => 'boolean',
            'hidden' => 'boolean',
            'sort_order' => 'integer',
        ];
    }
}
