<?php

declare(strict_types=1);

namespace App\Models\Communication;

use App\Services\Platform\Tenancy\BelongsToSchool;
use Illuminate\Database\Eloquent\Model;

class CommunicationOperation extends Model
{
    use BelongsToSchool;

    protected $table = 'communication_operations';

    protected $guarded = ['id', 'school_id'];

    protected $hidden = ['receipt', 'request_hash'];

    protected function casts(): array
    {
        return ['receipt' => 'encrypted:array'];
    }
}
