<?php

declare(strict_types=1);

namespace App\Models\Communication;

use App\Services\Platform\Tenancy\BelongsToSchool;
use Illuminate\Database\Eloquent\Model;

class MessageRead extends Model
{
    use BelongsToSchool;

    protected $table = 'message_reads';

    protected $guarded = ['id', 'school_id'];

    protected $hidden = [];

    protected function casts(): array
    {
        return ['read_at' => 'datetime'];
    }
}
