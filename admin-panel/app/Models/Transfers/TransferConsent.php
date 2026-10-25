<?php

declare(strict_types=1);

namespace App\Models\Transfers;

use App\Services\Platform\Tenancy\BelongsToSchool;
use Illuminate\Database\Eloquent\Model;

class TransferConsent extends Model
{
    use BelongsToSchool;

    protected $table = 'transfer_consents';

    protected $guarded = ['id', 'school_id'];

    protected $hidden = ['contents', 'manifest', 'signature', 'artifact_path', 'cipher_sha256', 'request_hash'];

    protected function casts(): array
    {
        return [];
    }

    protected static function booted(): void
    {
        static::updating(fn () => abort(409, 'Completed transfer records are immutable.'));
        static::deleting(fn () => abort(409, 'Completed transfer records must be retained.'));
    }
}
