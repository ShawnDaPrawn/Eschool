<?php

declare(strict_types=1);

namespace App\Models\Transfers;

use App\Casts\UtcInstant;
use App\Services\Platform\Tenancy\BelongsToSchool;
use Illuminate\Database\Eloquent\Model;

class TransferStage extends Model
{
    use BelongsToSchool;

    protected $table = 'transfer_stages';

    protected $guarded = ['id', 'school_id'];

    protected $hidden = ['contents', 'manifest', 'signature', 'artifact_path', 'cipher_sha256', 'request_hash'];

    protected function casts(): array
    {
        return ['expires_at' => UtcInstant::class, 'adopted' => 'boolean'];
    }
}
