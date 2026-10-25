<?php

declare(strict_types=1);

namespace App\Services\Transfers;

use App\Models\Transfers\TransferPackage;
use App\Models\Transfers\TransferStage;
use App\Models\User;
use App\Services\Academics\Transitions\ExitSigning;
use App\Services\Platform\Tenancy\CurrentSchool;
use Illuminate\Support\Facades\Crypt;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use RuntimeException;
use ZipArchive;

class TransferVault
{
    public const DISK = 'academic_private';

    public function stage(User $actor, string $operation, string $hash): TransferStage
    {
        $uuid = (string) Str::uuid();
        $row = TransferStage::create(['actor_id' => $actor->id, 'operation_id' => $operation, 'request_hash' => $hash, 'public_id' => $uuid,
            'path' => app(CurrentSchool::class)->storagePath('transfer-staging').'/'.$uuid.'.enc', 'expires_at' => now()->addHour(), 'adopted' => false]);
        abort_unless(hash_equals($row->request_hash, $hash), 409, 'This operation already stages another change.');
        abort_if($row->expires_at->lte(now()) && ! $row->adopted, 409, 'This uncommitted transfer copy expired. Compare the current transfer before starting a new operation.');

        return $row;
    }

    public function build(User $actor, array $contents, string $publicId): array
    {
        $root = $this->scratchRoot();
        if (! is_dir($root) && ! mkdir($root, 0700, true) && ! is_dir($root)) {
            throw new RuntimeException('Private transfer scratch unavailable.');
        }
        $scratch = tempnam($root, 'transfer-');
        if ($scratch === false) {
            throw new RuntimeException('Private transfer scratch unavailable.');
        }chmod($scratch, 0600);
        $zip = new ZipArchive;
        $open = false;
        try {
            if ($zip->open($scratch, ZipArchive::CREATE | ZipArchive::OVERWRITE) !== true) {
                throw new RuntimeException('Private transfer archive unavailable.');
            }
            $open = true;
            $files = [];
            $total = 0;
            $maximum = (int) config('school_transfers.maximum_bytes');
            foreach (['reports' => 'report', 'documents' => 'document'] as $key => $kind) {
                foreach ($contents[$key] as $item) {
                    $file = app(TransferEvidence::class)->file($actor, $contents, $kind, $item);
                    $total += strlen($file['bytes']);
                    abort_if($total > $maximum, 422, 'Select fewer files for this transfer.');
                    if (! $zip->addFromString($file['path'], $file['bytes'])) {
                        throw new RuntimeException('Private transfer archive unavailable.');
                    }
                    $files[] = ['path' => $file['path'], 'bytes' => strlen($file['bytes']), 'sha256' => hash('sha256', $file['bytes'])];
                    unset($file);
                }
            }
            $manifest = json_encode(['format' => 'navigateed-school-transfer-v1', 'package_id' => $publicId, 'contents' => $contents, 'files' => $files], JSON_THROW_ON_ERROR | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
            abort_if($total + strlen($manifest) > $maximum, 422, 'Select fewer files for this transfer.');
            $signature = app(ExitSigning::class)->sign($manifest);
            if (! $zip->addFromString('manifest.json', $manifest) || ! $zip->addFromString('signature.json', json_encode($signature, JSON_THROW_ON_ERROR))) {
                throw new RuntimeException('Private transfer archive unavailable.');
            }
            if (! $zip->close()) {
                throw new RuntimeException('Private transfer archive unavailable.');
            }
            $open = false;
            $bytes = file_get_contents($scratch);
            if (! is_string($bytes)) {
                throw new RuntimeException('Private transfer archive unavailable.');
            }
            abort_if(strlen($bytes) > $maximum, 422, 'Select fewer files for this transfer.');

            return ['manifest' => $manifest, 'signature' => $signature, 'fingerprint' => hash('sha256', $manifest), 'bytes' => $bytes, 'artifact_sha256' => hash('sha256', $bytes), 'artifact_bytes' => strlen($bytes)];
        } finally {
            if ($open) {
                $zip->close();
            }if (is_file($scratch)) {
                unlink($scratch);
            }
        }
    }

    public function put(TransferStage $stage, string $bytes): string
    {
        $this->stagePath($stage);
        $cipher = Crypt::encryptString($bytes);
        if (! Storage::disk(self::DISK)->put($stage->path, $cipher)) {
            throw new RuntimeException('Private transfer storage unavailable.');
        }

        return hash('sha256', $cipher);
    }

    public function verify(TransferPackage $row): void
    {
        $expected = app(ExitSigning::class)->sign('')['public_key'];
        $sig = $row->signature;
        abort_unless(is_array($sig) && is_string($sig['public_key'] ?? null) && is_string($sig['signature'] ?? null) && is_string($sig['algorithm'] ?? null), 409, 'Transfer verification failed.');
        $public = base64_decode((string) ($sig['public_key'] ?? ''), true);
        $signature = base64_decode((string) ($sig['signature'] ?? ''), true);
        abort_unless(($sig['algorithm'] ?? null) === 'Ed25519' && hash_equals($expected, (string) ($sig['public_key'] ?? '')) && is_string($public) && strlen($public) === SODIUM_CRYPTO_SIGN_PUBLICKEYBYTES && is_string($signature) && strlen($signature) === SODIUM_CRYPTO_SIGN_BYTES, 409, 'Transfer verification failed.');
        abort_unless(hash_equals($row->fingerprint, hash('sha256', $row->manifest)) && sodium_crypto_sign_verify_detached($signature, $row->manifest, $public), 409, 'Transfer verification failed.');
        $parsed = json_decode($row->manifest, true, flags: JSON_THROW_ON_ERROR);
        abort_unless(($parsed['package_id'] ?? null) === $row->public_id && ($parsed['contents'] ?? null) === $row->contents, 409, 'Transfer verification failed.');
        $c = $row->contents;
        abort_unless((int) ($c['from_school_id'] ?? 0) === (int) $row->school_id && ($c['to_school_id'] ?? null) === ($row->to_school_id === null ? null : (int) $row->to_school_id)
            && (int) ($c['source_learner_id'] ?? 0) === (int) $row->learner_id && (int) ($c['guardian_id'] ?? 0) === (int) $row->guardian_id && (int) ($c['session_year_id'] ?? 0) === (int) $row->session_year_id
            && ($c['last_attended_on'] ?? null) === $row->last_attended_on->toDateString() && ($c['starts_on'] ?? null) === $row->starts_on->toDateString() && ($c['source_enrolment_fingerprint'] ?? null) === $row->source_state_hash
            && ($row->to_school_id !== null || ($c['to_school_name'] ?? null) === $row->external_school), 409, 'Transfer routing or consent verification failed.');
    }

    public function readSource(TransferPackage $row): string
    {
        $this->verify($row);
        $prefix = app(CurrentSchool::class)->storagePath('transfer-staging').'/';
        abort_unless(str_starts_with($row->artifact_path, $prefix) && preg_match('/^[0-9a-f-]{36}\.enc$/D', substr($row->artifact_path, strlen($prefix))) === 1, 404);

        return $this->read($row->artifact_path, $row->cipher_sha256, $row->artifact_sha256, (int) $row->artifact_bytes);
    }

    public function read(string $path, string $cipherHash, string $plainHash, int $size): string
    {
        $disk = Storage::disk(self::DISK);
        abort_unless($disk->exists($path) && $disk->size($path) <= (int) config('school_transfers.maximum_bytes') * 2 + 4096, 409, 'The private transfer copy needs restoration.');
        $cipher = $disk->get($path);
        abort_unless(hash_equals($cipherHash, hash('sha256', $cipher)), 409, 'Transfer verification failed.');
        $plain = Crypt::decryptString($cipher);
        abort_unless(strlen($plain) === $size && hash_equals($plainHash, hash('sha256', $plain)), 409, 'Transfer verification failed.');

        return $plain;
    }

    public function discard(TransferStage $stage): void
    {
        $fresh = TransferStage::find($stage->id);
        if ($fresh === null || $fresh->adopted) {
            return;
        }
        $this->stagePath($fresh);
        Storage::disk(self::DISK)->delete($fresh->path);
    }

    public function stagePath(TransferStage $stage): void
    {
        abort_unless((int) $stage->school_id === app(CurrentSchool::class)->id() && Str::isUuid($stage->public_id) && $stage->path === app(CurrentSchool::class)->storagePath('transfer-staging').'/'.$stage->public_id.'.enc', 404);
    }

    public function scratchRoot(): string
    {
        return storage_path('app/private/transfer-scratch');
    }

    public function pruneScratch(): void
    {
        foreach (glob($this->scratchRoot().'/transfer-*') ?: [] as $path) {
            if (! is_link($path) && is_file($path) && filemtime($path) < time() - 3600) {
                unlink($path);
            }
        }
    }
}
