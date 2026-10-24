<?php

declare(strict_types=1);

namespace App\Services\Academics\Reports;

use App\Models\Academics\ReportBatch;
use App\Models\Academics\ReportCard;
use App\Models\Academics\ReportComment;
use App\Models\Academics\ReportCommentBank;
use App\Models\Academics\ReportTemplate;
use App\Models\ClassSection;
use App\Models\Platform\School;
use App\Models\User;
use App\Policies\Academics\ReportPolicy;
use App\Services\Academics\AcademicAuthority;
use App\Services\Platform\Audit\Audit;
use App\Services\Platform\Events\Outbox;
use App\Services\Platform\Events\ReportApproved;
use App\Services\Platform\Events\ReportPublished;
use App\Services\Platform\Tenancy\CurrentSchool;
use Illuminate\Support\Facades\Crypt;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\ValidationException;

class ReportBatches
{
    public static function selectionRules(): array
    {
        return ['template_id' => ['required', 'integer', 'min:1'], 'session_year_id' => ['required', 'integer', 'min:1'], 'term_id' => ['present', 'nullable', 'integer', 'min:1'],
            'class_section_id' => ['required', 'integer', 'min:1'], 'learner_ids' => ['required', 'array', 'min:1', 'max:500'], 'learner_ids.*' => ['required', 'integer', 'min:1', 'distinct'],
            'mode' => ['required', 'in:official,running']];
    }

    public static function actionRules(): array
    {
        return ['revision' => ['required', 'integer', 'min:1'], 'fingerprint' => ['required', 'string', 'regex:/^[a-f0-9]{64}$/']];
    }

    private function lock(): void
    {
        School::whereKey(app(CurrentSchool::class)->id())->lockForUpdate()->firstOrFail();
    }

    public function selection(User $actor, array $input): array
    {
        $authority = app(AcademicAuthority::class);
        $authority->staff($actor);
        $data = Validator::make($input, self::selectionRules())->validate();
        app(ReportPolicy::class)->scope($actor, (int) $data['session_year_id'], (int) $data['class_section_id']);
        foreach (['template_id', 'session_year_id', 'class_section_id'] as $key) {
            $data[$key] = (int) $data[$key];
        }
        $data['term_id'] = $data['term_id'] === null ? null : (int) $data['term_id'];
        $data['learner_ids'] = array_map('intval', $data['learner_ids']);
        sort($data['learner_ids']);

        return $data;
    }

    private function prepared(array $selection): array
    {
        $template = ReportTemplate::findOrFail($selection['template_id']);
        $evidence = app(ReportEvidence::class)->collect($selection);
        $design = ['template_id' => (int) $template->id, 'template_revision' => $template->revision, 'name' => $template->name] + $template->design;
        $design['crest'] = $design['show_crest'] ? app(ReportDesigns::class)->crest() : null;

        return $evidence + ['design' => $design, 'preview_fingerprint' => hash('sha256', json_encode([$selection, $evidence['fingerprint'], $design], JSON_THROW_ON_ERROR))];
    }

    public function preview(User $actor, array $input): array
    {
        $this->selection($actor, $input);

        return DB::transaction(function () use ($actor, $input) {
            $this->lock();
            $selection = $this->selection($actor, $input);
            $prepared = $this->prepared($selection);

            return ['selection' => $selection, 'fingerprint' => $prepared['preview_fingerprint'], 'can_prepare' => $prepared['can_prepare'], 'design' => $prepared['design'], 'cards' => $prepared['cards']];
        }, 3);
    }

    public function prepare(User $actor, array $input): array
    {
        $this->selection($actor, $input);
        $write = Validator::make($input, ['fingerprint' => ['required', 'string', 'regex:/^[a-f0-9]{64}$/']])->validate();

        return DB::transaction(function () use ($actor, $input, $write) {
            $this->lock();
            $selection = $this->selection($actor, $input);
            $prepared = $this->prepared($selection);
            abort_unless(hash_equals($prepared['preview_fingerprint'], $write['fingerprint']), 409, __('Report evidence changed. Preview the selected learners again.'));
            abort_unless($prepared['can_prepare'], 409, __('Complete these results or explicitly choose a provisional running report. Other learners can progress independently.'));
            $batch = ReportBatch::create(['report_template_id' => $selection['template_id'], 'session_year_id' => $selection['session_year_id'], 'term_id' => $selection['term_id'],
                'class_section_id' => $selection['class_section_id'], 'grade_level_id' => $prepared['grade_level_id'], 'mode' => $selection['mode'], 'state' => 'draft', 'revision' => 1, 'selection' => $selection,
                'design' => $prepared['design'], 'signature_data' => [], 'source_fingerprint' => $prepared['fingerprint'], 'fingerprint' => $prepared['preview_fingerprint'], 'created_by' => $actor->id]);
            foreach ($prepared['cards'] as $card) {
                ReportCard::create(['report_batch_id' => $batch->id, 'learner_id' => $card['learner_id'], 'payload' => $card]);
            }
            $batch->forceFill(['fingerprint' => $this->fingerprint($batch)])->save();
            Audit::record('academics.report_prepared', $batch, [], ['fingerprint' => $batch->fingerprint, 'learner_ids' => $selection['learner_ids'], 'mode' => $selection['mode']]);

            return $this->data($batch);
        }, 3);
    }

    private function fingerprint(ReportBatch $batch): string
    {
        $comments = ReportComment::whereHas('card', fn ($q) => $q->where('report_batch_id', $batch->id))->orderBy('report_card_id')->orderBy('scope_key')->get()
            ->map(fn ($comment) => [(int) $comment->report_card_id, $comment->scope_key, $comment->revision, $comment->text])->all();

        return hash('sha256', json_encode([$batch->source_fingerprint, $batch->design, $batch->signature_data ?? [], $comments], JSON_THROW_ON_ERROR));
    }

    private function mutable(ReportBatch $batch, int $revision): void
    {
        abort_if($batch->state === 'released' || $batch->revision !== $revision, 409, __('The report changed or was released. Reload; published reports need a new version.'));
    }

    private function reset(ReportBatch $batch): void
    {
        $before = ['state' => $batch->state, 'revision' => $batch->revision, 'fingerprint' => $batch->fingerprint, 'grade_signed_by' => $batch->grade_signed_by, 'principal_signed_by' => $batch->principal_signed_by];
        $batch->forceFill(['state' => 'draft', 'grade_signed_by' => null, 'grade_signed_at' => null, 'principal_signed_by' => null, 'principal_signed_at' => null,
            'signature_data' => [], 'revision' => $batch->revision + 1]);
        $batch->forceFill(['fingerprint' => $this->fingerprint($batch)])->save();
        Audit::record('academics.report_signatures_reset', $batch, $before, ['state' => $batch->state, 'revision' => $batch->revision, 'fingerprint' => $batch->fingerprint]);
    }

    public function refresh(User $actor, int $id, array $input): array
    {
        app(ReportPolicy::class)->batch($actor, $id);
        $write = Validator::make($input, self::actionRules())->validate();

        return DB::transaction(function () use ($actor, $id, $write) {
            $this->lock();
            $batch = app(ReportPolicy::class)->batch($actor, $id);
            $this->mutable($batch, (int) $write['revision']);
            $evidence = $this->evidence($batch);
            abort_unless(hash_equals($write['fingerprint'], $evidence['fingerprint']), 409, __('The report evidence changed. Preview it again before refreshing.'));
            abort_unless($evidence['can_prepare'], 409, __('Selected results are incomplete. Review the selected learners or create a provisional batch.'));
            $cards = $batch->cards->keyBy('learner_id');
            foreach ($evidence['cards'] as $payload) {
                $cards->get($payload['learner_id'])->forceFill(['payload' => $payload])->save();
            }
            $before = ['fingerprint' => $batch->fingerprint, 'revision' => $batch->revision];
            $batch->source_fingerprint = $evidence['fingerprint'];
            $this->reset($batch);
            Audit::record('academics.report_refreshed', $batch, $before, ['fingerprint' => $batch->fingerprint, 'revision' => $batch->revision]);

            return $this->data($batch);
        }, 3);
    }

    public static function commentRules(): array
    {
        return ['subject_id' => ['present', 'nullable', 'integer', 'min:1'], 'text' => ['present', 'nullable', 'string', 'max:3000'], 'bank_id' => ['nullable', 'integer', 'min:1'],
            'revision' => ['required', 'integer', 'min:0'], 'batch_revision' => ['required', 'integer', 'min:1'], 'reason' => ['nullable', 'string', 'max:500']];
    }

    public function comment(User $actor, int $cardId, array $input): array
    {
        $rules = self::commentRules();
        $data = Validator::make($input, $rules)->validate();
        $subjectId = $data['subject_id'] === null ? null : (int) $data['subject_id'];
        $card = ReportCard::with('batch')->findOrFail($cardId);
        app(ReportPolicy::class)->comment($actor, $card, $subjectId);

        return DB::transaction(function () use ($actor, $cardId, $data, $subjectId) {
            $this->lock();
            $card = ReportCard::with('batch')->findOrFail($cardId);
            $override = app(ReportPolicy::class)->comment($actor, $card, $subjectId);
            if ($override && mb_strlen(trim($data['reason'] ?? '')) < 3) {
                throw ValidationException::withMessages(['reason' => __('Give a reason for the report comment correction.')]);
            }
            $batch = $card->batch;
            abort_if($batch->state === 'released' || ($batch->state !== 'draft' && $batch->revision !== (int) $data['batch_revision']), 409, __('Report approval changed. Reload before correcting a signed report.'));
            // Independent draft comment scopes do not conflict with one another.
            // The row revision below still refuses a competing edit to this text.
            $scope = $subjectId === null ? 'homeroom' : 'subject:'.$subjectId;
            $row = ReportComment::where('report_card_id', $cardId)->where('scope_key', $scope)->first() ?? new ReportComment(['report_card_id' => $cardId, 'scope_key' => $scope, 'revision' => 0]);
            abort_unless($row->revision === (int) $data['revision'], 409, __('This comment changed. Compare it before saving.'));
            $text = $this->commentText($data);
            $before = $row->getAttributes();
            $row->fill(['text' => trim($text), 'author_id' => $actor->id, 'revision' => $row->revision + 1])->save();
            $this->reset($batch);
            Audit::record('academics.report_comment_saved', $row, $before, $row->getAttributes() + ['authority_override' => $override, 'reason_ciphertext' => empty($data['reason']) ? null : Crypt::encryptString($data['reason'])]);

            return ['id' => (int) $row->id, 'revision' => $row->revision, 'batch_revision' => $batch->revision, 'fingerprint' => $batch->fingerprint, 'text' => $row->text];
        }, 3);
    }

    /** Resolve validated bank selection before displaying an explicitly reviewed draft. */
    public function commentText(array $data): string
    {
        if (! isset($data['bank_id'])) {
            return $data['text'] ?? '';
        }
        $subject = $data['subject_id'] === null ? null : (int) $data['subject_id'];

        return ReportCommentBank::where('active', true)->where('scope', $subject === null ? 'homeroom' : 'subject')->where('subject_id', $subject)->findOrFail($data['bank_id'])->text;
    }

    private function evidence(ReportBatch $batch): array
    {
        $room = ClassSection::with('class')->findOrFail($batch->class_section_id);
        abort_unless((int) $room->class?->grade_level_id === (int) $batch->grade_level_id, 409, __('The homeroom grade changed. Prepare a new report in its current grade.'));

        return app(ReportEvidence::class)->collect($batch->selection);
    }

    private function current(ReportBatch $batch): void
    {
        $evidence = $this->evidence($batch);
        abort_unless($evidence['can_prepare'] && hash_equals($batch->source_fingerprint, $evidence['fingerprint']), 409, __('Marks, roster or attendance changed. Refresh the report and obtain new signatures.'));
    }

    public function approve(User $actor, int $id, string $kind, array $input): array
    {
        $batch = app(ReportPolicy::class)->batch($actor, $id);
        app(ReportPolicy::class)->sign($actor, $batch, $kind);
        $write = Validator::make($input, self::actionRules())->validate();

        return DB::transaction(function () use ($actor, $id, $kind, $write) {
            $this->lock();
            $batch = app(ReportPolicy::class)->batch($actor, $id);
            app(ReportPolicy::class)->sign($actor, $batch, $kind);
            $this->mutable($batch, (int) $write['revision']);
            abort_unless(hash_equals($batch->fingerprint, $write['fingerprint']), 409, __('Preview the current report before signing.'));
            $this->current($batch);
            $before = ['state' => $batch->state, 'revision' => $batch->revision, 'fingerprint' => $batch->fingerprint, 'grade_signed_by' => $batch->grade_signed_by, 'principal_signed_by' => $batch->principal_signed_by];
            $principal = $kind === 'principal';
            abort_unless($batch->state === ($principal ? 'grade_signed' : 'draft'), 409, __('A grade head signs the draft before the principal.'));
            $name = User::findOrFail($actor->id);
            $signatures = $batch->signature_data ?? [];
            $signatures[$kind] = ['name' => trim($name->first_name.' '.$name->last_name), 'signed_at' => now()->toISOString(), 'revision' => $batch->revision + 1];
            $batch->forceFill(['state' => $principal ? 'approved' : 'grade_signed', $principal ? 'principal_signed_by' : 'grade_signed_by' => $actor->id,
                $principal ? 'principal_signed_at' : 'grade_signed_at' => now(), 'signature_data' => $signatures, 'revision' => $batch->revision + 1]);
            $batch->forceFill(['fingerprint' => $this->fingerprint($batch)])->save();
            Audit::record('academics.report_signed', $batch, $before, ['state' => $batch->state, 'signed_by' => (int) $actor->id, 'kind' => $kind, 'fingerprint' => $batch->fingerprint, 'revision' => $batch->revision]);
            if ($principal) {
                Outbox::record(new ReportApproved(app(CurrentSchool::class)->id(), $id, $batch->fingerprint));
            }

            return $this->data($batch);
        }, 3);
    }

    public function sourcePreview(User $actor, int $id): array
    {
        app(ReportPolicy::class)->batch($actor, $id);

        return DB::transaction(function () use ($actor, $id) {
            $this->lock();
            $batch = app(ReportPolicy::class)->batch($actor, $id);

            return $this->evidence($batch);
        }, 3);
    }

    public function retry(User $actor, int $id, array $input): array
    {
        app(ReportPolicy::class)->batch($actor, $id);
        $write = Validator::make($input, self::actionRules())->validate();

        return DB::transaction(function () use ($actor, $id, $write) {
            $this->lock();
            $batch = app(ReportPolicy::class)->batch($actor, $id);
            $this->mutable($batch, (int) $write['revision']);
            abort_unless(in_array($batch->state, ['approved', 'ready'], true) && hash_equals($batch->fingerprint, $write['fingerprint']), 409);
            $this->current($batch);
            Outbox::record(new ReportApproved(app(CurrentSchool::class)->id(), $id, $batch->fingerprint));

            return $this->data($batch);
        }, 3);
    }

    public function releasePreview(User $actor, int $id): array
    {
        app(ReportPolicy::class)->batch($actor, $id);

        return DB::transaction(function () use ($actor, $id) {
            $this->lock();
            $batch = app(ReportPolicy::class)->batch($actor, $id);
            if ($batch->state !== 'released') {
                $this->current($batch);
            }
            $data = $this->data($batch);
            $data['can_release'] = in_array($batch->state, ['approved', 'ready', 'released'], true) && $batch->cards()->get()->every(fn ($card) => app(ReportArtifacts::class)->available($card, $batch->fingerprint));

            return $data;
        }, 3);
    }

    public function release(User $actor, int $id, array $input): array
    {
        app(ReportPolicy::class)->batch($actor, $id);
        $write = Validator::make($input, self::actionRules())->validate();

        return DB::transaction(function () use ($actor, $id, $write) {
            $this->lock();
            $batch = app(ReportPolicy::class)->batch($actor, $id);
            abort_unless(hash_equals($batch->fingerprint, $write['fingerprint']), 409, __('Preview the current reports before release.'));
            if ($batch->state === 'released') {
                return $this->data($batch);
            }
            $this->mutable($batch, (int) $write['revision']);
            $preview = $this->releasePreview($actor, $id);
            abort_unless($preview['can_release'], 409, __('Wait for every selected PDF before releasing this batch.'));
            $before = ['state' => $batch->state, 'revision' => $batch->revision, 'fingerprint' => $batch->fingerprint];
            $batch->forceFill(['state' => 'released', 'released_at' => now(), 'released_by' => $actor->id, 'revision' => $batch->revision + 1])->save();
            Audit::record('academics.report_released', $batch, $before, ['state' => 'released', 'released_by' => (int) $actor->id, 'fingerprint' => $batch->fingerprint, 'learner_ids' => $batch->selection['learner_ids']]);
            Outbox::record(new ReportPublished(app(CurrentSchool::class)->id(), $id));

            return $this->data($batch);
        }, 3);
    }

    public function data(ReportBatch $batch): array
    {
        return ['id' => (int) $batch->id, 'state' => $batch->state, 'revision' => $batch->revision, 'fingerprint' => $batch->fingerprint, 'source_fingerprint' => $batch->source_fingerprint,
            'selection' => $batch->selection, 'design' => $batch->design, 'signatures' => (object) ($batch->signature_data ?? []), 'released_at' => $batch->released_at?->toISOString(),
            'cards' => $batch->cards()->orderBy('learner_id')->get()->map(fn ($card) => ['id' => (int) $card->id, 'learner_id' => (int) $card->learner_id, 'payload' => $card->payload,
                'pdf_ready' => app(ReportArtifacts::class)->available($card, $batch->fingerprint), 'generated_at' => $card->generated_at?->toISOString()])->all()];
    }
}
