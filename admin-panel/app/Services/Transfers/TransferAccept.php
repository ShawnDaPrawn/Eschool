<?php

declare(strict_types=1);

namespace App\Services\Transfers;

use App\Events\Transfers\TransferCompleted;
use App\Models\Students;
use App\Models\Transfers\TransferPackage;
use App\Models\Transfers\TransferReceipt;
use App\Models\Transfers\TransferStage;
use App\Models\User;
use App\Services\Platform\Audit\Audit;
use App\Services\Platform\Tenancy\CurrentSchool;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;

class TransferAccept
{
    private function source(TransferPackage $package, callable $work): mixed
    {
        return app(CurrentSchool::class)->run((int) $package->school_id, function () use ($package, $work) {
            $fresh = TransferPackage::findOrFail($package->id);
            $signer = app(TransferPackages::class)->sourceCurrent($fresh);
            $learner = Students::with('user')->findOrFail($fresh->learner_id);
            $parent = User::findOrFail($fresh->guardian_id);
            abort_unless((int) $learner->user_id === (int) $fresh->contents['learner_user_id'], 409, 'The learner identity changed. Review a new transfer.');

            return $work($fresh, $signer, $learner->user, $parent);
        });
    }

    public function accept(User $actor, int $id, array $input): TransferReceipt
    {
        $a = app(TransferAuthority::class);
        $a->staff($actor, true);
        $d = Validator::make($input, [
            'operation_id' => ['required', 'uuid'], 'revision' => ['required', 'integer', 'min:1'], 'fingerprint' => ['required', 'string', 'regex:/^[a-f0-9]{64}$/'],
            'session_year_id' => ['required', 'integer', 'min:1'], 'class_section_id' => ['required', 'integer', 'min:1'], 'category_id' => ['required', 'integer', 'min:1'], 'confirmation' => ['required', 'accepted']])->validate();
        foreach (['revision', 'session_year_id', 'class_section_id', 'category_id'] as $key) {
            $d[$key] = (int) $d[$key];
        }
        $keys = app(TransferOperations::class);
        $hash = $keys->hash('accept', [$id, $d['revision'], $d['fingerprint'], $d['session_year_id'], $d['class_section_id'], $d['category_id']]);
        if ($prior = $keys->replay($actor, $d['operation_id'], $hash)) {
            return $a->receipt($actor, (int) $prior->record_id);
        }
        $package = $a->incoming($actor, $id, true);
        abort_unless($package->revision === $d['revision'] && hash_equals($package->fingerprint, $d['fingerprint']) && $package->state === 'released', 409, 'Review the current released transfer.');
        app(TransferEnrollment::class)->placement($d, $package->starts_on->toDateString());
        $bytes = $this->source($package, fn ($p) => app(TransferVault::class)->readSource($p));
        $vault = app(TransferVault::class);
        $stage = $vault->stage($actor, $d['operation_id'], $hash);
        try {
            $cipher = $vault->put($stage, $bytes);
            unset($bytes);

            return DB::transaction(function () use ($actor, $id, $d, $keys, $hash, $package, $stage, $cipher, $vault, $a) {
                app(TransferPackages::class)->lockSchools([(int) $package->school_id, app(CurrentSchool::class)->id()]);
                app(TransferLocks::class)->authority([(int) $package->school_id, app(CurrentSchool::class)->id()], [$actor->id, $package->released_by, $package->guardian_id, $package->contents['learner_user_id']]);
                $a->staff($actor, true);
                if ($prior = $keys->replay($actor, $d['operation_id'], $hash)) {
                    return $a->receipt($actor, (int) $prior->record_id);
                }
                $fresh = TransferPackage::acrossSchools()->where('to_school_id', app(CurrentSchool::class)->id())->lockForUpdate()->findOrFail($id);
                abort_unless($fresh->state === 'released' && $fresh->revision === $d['revision'] && hash_equals($fresh->fingerprint, $d['fingerprint']), 409, 'This transfer changed or was already accepted.');
                $destination = app(CurrentSchool::class)->id();
                [$signer,$identity,$parent] = $this->source($fresh, fn ($p, $s, $i, $g) => [$s, $i, $g]);
                $stage = TransferStage::lockForUpdate()->findOrFail($stage->id);
                $vault->stagePath($stage);
                abort_if($stage->expires_at->lte(now()) || $stage->adopted, 409, 'The staged copy expired.');
                app(CurrentSchool::class)->run((int) $fresh->school_id, fn () => app(TransferEnrollment::class)->closeSource($signer, $fresh));
                $learner = app(TransferEnrollment::class)->receive($actor, $identity, $parent, $fresh, $d);
                $receipt = TransferReceipt::create(['transfer_package_id' => $fresh->id, 'learner_id' => $learner->id, 'accepted_by' => $actor->id, 'source_school_id' => $fresh->school_id,
                    'starts_on' => $fresh->starts_on->toDateString(), 'contents' => $fresh->contents, 'manifest' => $fresh->manifest, 'signature' => $fresh->signature,
                    'artifact_path' => $stage->path, 'artifact_sha256' => $fresh->artifact_sha256, 'artifact_bytes' => $fresh->artifact_bytes, 'cipher_sha256' => $cipher]);
                $stage->update(['adopted' => true]);
                app(CurrentSchool::class)->run((int) $fresh->school_id, fn () => $fresh->update(['state' => 'accepted', 'accepted_at' => now(), 'revision' => $fresh->revision + 1]));
                if (! ($fresh->contents['export_only'] ?? false)) {
                    event(new TransferCompleted((int) $fresh->school_id, (int) $fresh->id, $destination, (int) $receipt->id, (int) $d['session_year_id']));
                }
                abort_unless(app(CurrentSchool::class)->id() === $destination, 403);
                $a->staff($actor, true);
                $keys->remember($actor, $d['operation_id'], $hash, 'receipt', (int) $receipt->id);
                Audit::record('transfer.accepted', $receipt, [], ['source_school_id' => (int) $fresh->school_id, 'source_package_id' => $fresh->public_id, 'learner_id' => (int) $learner->id, 'released_by' => (int) $signer->id]);

                return $receipt;
            }, 3);
        } finally {
            $vault->discard($stage);
        }
    }
}
