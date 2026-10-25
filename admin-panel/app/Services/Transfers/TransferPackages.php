<?php

declare(strict_types=1);

namespace App\Services\Transfers;

use App\Models\Platform\School;
use App\Models\Students;
use App\Models\Transfers\TransferConsent;
use App\Models\Transfers\TransferPackage;
use App\Models\Transfers\TransferStage;
use App\Models\User;
use App\Services\Academics\Transitions\TransitionContext;
use App\Services\Platform\Audit\Audit;
use App\Services\Platform\Tenancy\CurrentSchool;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;

class TransferPackages
{
    public function lockSchools(array $ids): void
    {
        $ids = array_values(array_unique(array_filter($ids)));
        sort($ids);
        School::whereIn('id', $ids)->orderBy('id')->lockForUpdate()->get();
    }

    public function operation(array $input, bool $revision = false): array
    {
        return Validator::make($input, ['operation_id' => ['required', 'uuid']] + ($revision ? ['revision' => ['required', 'integer', 'min:1']] : []))->validate();
    }

    public function prepare(User $actor, array $input): TransferPackage
    {
        $d = app(TransferEvidence::class)->selection($actor, $input);
        $op = $this->operation($input);
        $keys = app(TransferOperations::class);
        $hash = $keys->hash('prepare', $d);
        $a = app(TransferAuthority::class);
        if ($previous = $keys->replay($actor, $op['operation_id'], $hash)) {
            return $a->outgoing($actor, (int) $previous->record_id);
        }
        $evidence = app(TransferEvidence::class)->collect($actor, $d);
        $vault = app(TransferVault::class);
        $stage = $vault->stage($actor, $op['operation_id'], $hash);
        $public = $stage->public_id;
        try {
            $bundle = $vault->build($actor, $evidence['contents'], $public);
            $cipher = $vault->put($stage, $bundle['bytes']);
            unset($bundle['bytes']);

            return DB::transaction(function () use ($actor, $d, $op, $hash, $keys, $a, $vault, $stage, $bundle, $cipher, $public, $evidence) {
                $this->lockSchools([app(CurrentSchool::class)->id()]);
                app(TransferLocks::class)->authority([app(CurrentSchool::class)->id()], [$actor->id, $d['guardian_id'], $evidence['contents']['learner_user_id']]);
                $a->staff($actor, true);
                if ($previous = $keys->replay($actor, $op['operation_id'], $hash)) {
                    return $a->outgoing($actor, (int) $previous->record_id);
                }
                $current = app(TransferEvidence::class)->collect($actor, $d);
                abort_unless($current === $evidence, 409, 'The source records changed. Review a fresh transfer.');
                $stage = TransferStage::lockForUpdate()->findOrFail($stage->id);
                $vault->stagePath($stage);
                abort_if($stage->expires_at->lte(now()) || $stage->adopted, 409);
                $row = TransferPackage::create(['public_id' => $public, 'to_school_id' => $d['to_school_id'], 'external_school' => $d['external_school'],
                    'learner_id' => $d['learner_id'], 'guardian_id' => $d['guardian_id'], 'requested_by' => $actor->id, 'session_year_id' => $evidence['contents']['session_year_id'],
                    'last_attended_on' => $d['last_attended_on'], 'starts_on' => $d['starts_on'], 'state' => 'awaiting_consent', 'revision' => 1, 'source_state_hash' => $evidence['source_state_hash'],
                    'fingerprint' => $bundle['fingerprint'], 'contents' => $evidence['contents'], 'manifest' => $bundle['manifest'], 'signature' => $bundle['signature'],
                    'artifact_path' => $stage->path, 'artifact_sha256' => $bundle['artifact_sha256'], 'artifact_bytes' => $bundle['artifact_bytes'], 'cipher_sha256' => $cipher,
                    'expires_at' => now()->addDays((int) config('school_transfers.request_days'))]);
                $stage->update(['adopted' => true]);
                $keys->remember($actor, $op['operation_id'], $hash, 'package', (int) $row->id);
                Audit::record('transfer.prepared', $row, [], ['public_id' => $row->public_id, 'to_school_id' => $row->to_school_id, 'fingerprint' => $row->fingerprint]);
                app(TransferNotices::class)->prepared($row);

                return $row;
            }, 3);
        } finally {
            $vault->discard($stage);
        }
    }

    public function consent(User $guardian, int $id, array $input): TransferPackage
    {
        $a = app(TransferAuthority::class);
        $initial = $a->ownConsent($guardian, $id, true);
        $op = $this->operation($input, true);
        $d = Validator::make($input, ['fingerprint' => ['required', 'string', 'regex:/^[a-f0-9]{64}$/'], 'version' => ['required', 'string', 'max:64'], 'confirmation' => ['required', 'accepted']])->validate();
        abort_unless($d['version'] === config('school_transfers.consent_version'), 422, 'Read and accept the current transfer consent.');
        $keys = app(TransferOperations::class);
        $hash = $keys->hash('consent', [$id, (int) $op['revision'], $d['fingerprint'], $d['version']]);

        return DB::transaction(function () use ($a, $guardian, $id, $op, $d, $keys, $hash, $initial) {
            $this->lockSchools([app(CurrentSchool::class)->id()]);
            app(TransferLocks::class)->authority([app(CurrentSchool::class)->id()], [$guardian->id, $initial->contents['learner_user_id']]);
            $row = $a->ownConsent($guardian, $id, true);
            if ($prior = $keys->replay($guardian, $op['operation_id'], $hash)) {
                return $a->ownConsent($guardian, (int) $prior->record_id);
            }
            $row = TransferPackage::lockForUpdate()->findOrFail($row->id);
            abort_unless($row->state === 'awaiting_consent' && $row->revision === (int) $op['revision'] && $row->expires_at->isFuture() && hash_equals($row->fingerprint, $d['fingerprint']), 409, 'The transfer changed or expired. Review its current contents.');
            app(TransferVault::class)->verify($row);
            TransferConsent::create(['transfer_package_id' => $row->id, 'guardian_id' => $guardian->id, 'decision' => 'approve', 'version' => $d['version'], 'fingerprint' => $row->fingerprint]);
            $row->update(['state' => 'consented', 'revision' => $row->revision + 1]);
            $keys->remember($guardian, $op['operation_id'], $hash, 'package', (int) $row->id);
            Audit::record('transfer.consented', $row, [], ['guardian_id' => (int) $guardian->id, 'fingerprint' => $row->fingerprint, 'version' => $d['version']]);

            return $row;
        }, 3);
    }

    public function consentCurrent(TransferPackage $row): void
    {
        $parent = User::findOrFail($row->guardian_id);
        app(TransferAuthority::class)->guardian($parent, (int) $row->learner_id);
        $consent = TransferConsent::where('transfer_package_id', $row->id)->orderByDesc('id')->first();
        abort_unless($consent !== null && $consent->decision === 'approve' && (int) $consent->guardian_id === (int) $row->guardian_id && hash_equals($row->fingerprint, $consent->fingerprint), 409, 'The guardian consent is no longer current.');
    }

    public function sourceCurrent(TransferPackage $row): User
    {
        abort_unless(in_array($row->state, ['consented', 'released'], true) && $row->expires_at->isFuture(), 409, 'This transfer is not available for release or acceptance.');
        $this->consentCurrent($row);
        app(TransferVault::class)->verify($row);
        $state = app(TransitionContext::class)->state((int) $row->learner_id);
        abort_unless(hash_equals($row->source_state_hash, hash('sha256', json_encode($state, JSON_THROW_ON_ERROR))), 409, 'The source enrolment changed. Ask the sending school to review a new transfer.');
        $signer = User::findOrFail($row->released_by ?? $row->requested_by);

        $signer = app(TransferAuthority::class)->staff($signer, true);
        $guardian = User::findOrFail($row->guardian_id);
        $learner = Students::with('user')->findOrFail($row->learner_id);
        app(TransferEvidence::class)->documents($signer, $guardian, $learner, $row->contents['selection']['document_ids']);

        return $signer;
    }

    public function release(User $actor, int $id, array $input): TransferPackage
    {
        $a = app(TransferAuthority::class);
        $initial = $a->outgoing($actor, $id, true);
        $op = $this->operation($input, true);
        $keys = app(TransferOperations::class);
        $hash = $keys->hash('release', [$id, (int) $op['revision']]);

        return DB::transaction(function () use ($actor, $id, $op, $keys, $hash, $a, $initial) {
            $this->lockSchools([app(CurrentSchool::class)->id()]);
            app(TransferLocks::class)->authority([app(CurrentSchool::class)->id()], [$actor->id, $initial->guardian_id, $initial->contents['learner_user_id']]);
            $row = $a->outgoing($actor, $id, true);
            if ($prior = $keys->replay($actor, $op['operation_id'], $hash)) {
                return $a->outgoing($actor, (int) $prior->record_id);
            }
            $row = TransferPackage::lockForUpdate()->findOrFail($id);
            abort_unless($row->state === 'consented' && $row->revision === (int) $op['revision'], 409, 'Review the current consented transfer.');
            $row->released_by = $actor->id;
            $this->sourceCurrent($row);
            app(TransferVault::class)->readSource($row);
            if ($row->to_school_id === null) {
                if (! ($row->contents['export_only'] ?? false)) {
                    app(TransferEnrollment::class)->closeSource($actor, $row);
                }
            } else {
                School::active()->findOrFail($row->to_school_id);
            }
            $row->fill(['state' => 'released', 'released_at' => now(), 'revision' => $row->revision + 1])->save();
            $keys->remember($actor, $op['operation_id'], $hash, 'package', (int) $row->id);
            Audit::record('transfer.released', $row, [], ['to_school_id' => $row->to_school_id, 'fingerprint' => $row->fingerprint, 'released_by' => (int) $actor->id]);

            return $row;
        }, 3);
    }

    public function cancel(User $actor, int $id, array $input, bool $guardian = false): TransferPackage
    {
        $a = app(TransferAuthority::class);
        $initial = $guardian ? $a->ownConsent($actor, $id, true) : $a->outgoing($actor, $id, true);
        $op = $this->operation($input, true);
        $keys = app(TransferOperations::class);
        $hash = $keys->hash($guardian ? 'withdraw' : 'cancel', [$id, (int) $op['revision']]);

        return DB::transaction(function () use ($actor, $id, $guardian, $op, $keys, $hash, $a, $initial) {
            $this->lockSchools([app(CurrentSchool::class)->id()]);
            app(TransferLocks::class)->authority([app(CurrentSchool::class)->id()], [$actor->id, $initial->guardian_id, $initial->contents['learner_user_id']]);
            $guardian ? $a->ownConsent($actor, $id, true) : $a->outgoing($actor, $id, true);
            if ($prior = $keys->replay($actor, $op['operation_id'], $hash)) {
                return $guardian ? $a->ownConsent($actor, (int) $prior->record_id) : $a->outgoing($actor, (int) $prior->record_id);
            }
            $row = TransferPackage::lockForUpdate()->findOrFail($id);
            abort_unless($row->revision === (int) $op['revision'] && $row->state !== 'accepted' && ! ($row->released_at !== null && $row->to_school_id === null), 409, 'Accepted institutional records cannot be withdrawn through this form.');
            if ($guardian) {
                TransferConsent::create(['transfer_package_id' => $row->id, 'guardian_id' => $actor->id, 'decision' => 'withdraw', 'version' => config('school_transfers.consent_version'), 'fingerprint' => $row->fingerprint]);
            }
            $row->update(['state' => 'cancelled', 'revision' => $row->revision + 1]);
            $keys->remember($actor, $op['operation_id'], $hash, 'package', (int) $row->id);
            Audit::record($guardian ? 'transfer.consent_withdrawn' : 'transfer.cancelled', $row, [], ['fingerprint' => $row->fingerprint]);

            return $row;
        }, 3);
    }
}
