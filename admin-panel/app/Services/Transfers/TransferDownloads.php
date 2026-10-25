<?php

declare(strict_types=1);

namespace App\Services\Transfers;

use App\Models\Transfers\TransferReceipt;
use App\Models\User;
use App\Services\Academics\Transitions\ExitSigning;
use App\Services\Platform\Tenancy\CurrentSchool;

class TransferDownloads
{
    public function read(User $actor, string $lane, int $id): string
    {
        $school = app(CurrentSchool::class)->id();
        $queries = app(TransferQueries::class);
        $info = $queries->show($actor, $lane, $id);
        abort_unless($info['can_download'], 409, 'This handoff copy has expired. The original school records remain retained.');
        $row = $queries->record($actor, $lane, $id);
        $vault = app(TransferVault::class);
        if ($row instanceof TransferReceipt) {
            $expected = app(CurrentSchool::class)->run((int) $row->source_school_id, fn () => app(ExitSigning::class)->sign('')['public_key']);
            $sig = $row->signature;
            $public = base64_decode((string) ($sig['public_key'] ?? ''), true);
            $signature = base64_decode((string) ($sig['signature'] ?? ''), true);
            $manifest = json_decode($row->manifest, true, flags: JSON_THROW_ON_ERROR);
            abort_unless(($sig['algorithm'] ?? null) === 'Ed25519' && hash_equals($expected, (string) ($sig['public_key'] ?? '')) && is_string($public) && strlen($public) === SODIUM_CRYPTO_SIGN_PUBLICKEYBYTES
                && is_string($signature) && strlen($signature) === SODIUM_CRYPTO_SIGN_BYTES && sodium_crypto_sign_verify_detached($signature, $row->manifest, $public)
                && ($manifest['contents'] ?? null) === $row->contents && (int) ($row->contents['to_school_id'] ?? 0) === app(CurrentSchool::class)->id()
                && (int) ($row->contents['from_school_id'] ?? 0) === (int) $row->source_school_id && ($row->contents['starts_on'] ?? null) === $row->starts_on->toDateString(), 409, 'Transfer verification failed.');
            $prefix = app(CurrentSchool::class)->storagePath('transfer-staging').'/';
            abort_unless(str_starts_with($row->artifact_path, $prefix) && preg_match('/^[0-9a-f-]{36}\.enc$/D', substr($row->artifact_path, strlen($prefix))) === 1, 404);
            $bytes = $vault->read($row->artifact_path, $row->cipher_sha256, $row->artifact_sha256, (int) $row->artifact_bytes);
        } else {
            $bytes = app(CurrentSchool::class)->run((int) $row->school_id, fn () => $vault->readSource($row));
        }
        // Disk/crypto callbacks must not turn a stale role or replaced school into a read.
        abort_unless(app(CurrentSchool::class)->id() === $school, 403);
        abort_unless($queries->show($actor, $lane, $id)['can_download'], 409, 'The handoff copy expired or was withdrawn.');

        return $bytes;
    }
}
