<?php

declare(strict_types=1);

namespace App\Models\Cards;

use App\Services\Platform\Tenancy\BelongsToSchool;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;

class CardPortrait extends Model
{
    use BelongsToSchool, HasUuids;

    protected $table = 'card_portraits';

    protected $guarded = ['id', 'school_id'];

    protected $hidden = ['path'];

    protected function casts(): array
    {
        return ['revision' => 'integer', 'size' => 'integer'];
    }
}
