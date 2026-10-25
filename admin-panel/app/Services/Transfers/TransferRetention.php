<?php

declare(strict_types=1);

namespace App\Services\Transfers;

use App\Models\Transfers\TransferPackage;
use App\Models\Transfers\TransferStage;
use App\Services\Platform\Tenancy\CurrentSchool;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;

class TransferRetention
{
    public function expire(): void
    {
        foreach (TransferStage::where('adopted', false)->where('expires_at', '<=', now())->orderBy('id')->pluck('id') as $id) {
            DB::transaction(function () use ($id): void {
                $row = TransferStage::where('adopted', false)->where('expires_at', '<=', now())->lockForUpdate()->find($id);
                if ($row === null) {
                    return;
                }
                app(TransferVault::class)->stagePath($row);
                Storage::disk(TransferVault::DISK)->delete($row->path);
                $row->delete();
            });
        }
        foreach (TransferPackage::where('expires_at', '<=', now())->orderBy('id')->pluck('id') as $id) {
            DB::transaction(function () use ($id): void {
                $row = TransferPackage::where('expires_at', '<=', now())->lockForUpdate()->find($id);
                if ($row === null) {
                    return;
                }
                $prefix = app(CurrentSchool::class)->storagePath('transfer-staging').'/';
                abort_unless(str_starts_with($row->artifact_path, $prefix) && preg_match('/^[0-9a-f-]{36}\.enc$/D', substr($row->artifact_path, strlen($prefix))) === 1, 409);
                Storage::disk(TransferVault::DISK)->delete($row->artifact_path);
                if (! in_array($row->state, ['accepted', 'cancelled', 'expired'], true)) {
                    $row->update(['state' => 'expired', 'revision' => $row->revision + 1]);
                }
            });
        }
    }
}
