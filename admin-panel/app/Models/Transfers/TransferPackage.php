<?php

declare(strict_types=1);

namespace App\Models\Transfers;

use App\Services\Platform\Tenancy\BelongsToSchool;
use Illuminate\Database\Eloquent\Model;

class TransferPackage extends Model
{
    use BelongsToSchool;

    protected $table = 'transfer_packages';

    protected $guarded = ['id', 'school_id'];

    protected $hidden = ['contents', 'manifest', 'signature', 'artifact_path', 'cipher_sha256', 'request_hash'];

    protected function casts(): array
    {
        return ['last_attended_on' => 'date', 'starts_on' => 'date', 'revision' => 'integer', 'contents' => 'encrypted:array', 'manifest' => 'encrypted', 'signature' => 'encrypted:array', 'expires_at' => 'datetime', 'released_at' => 'datetime', 'accepted_at' => 'datetime'];
    }

    protected static function booted(): void
    {
        static::updating(fn (self $row) => abort_unless(array_diff(array_keys($row->getDirty()), ['state', 'revision', 'released_by', 'released_at', 'accepted_at', 'expires_at', 'updated_at']) === [], 409, 'Frozen transfer contents and routing cannot be changed.'));
        static::deleting(fn () => abort(409, 'Transfer consent history must be retained.'));
    }
}
