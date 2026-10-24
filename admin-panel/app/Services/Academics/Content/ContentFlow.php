<?php

declare(strict_types=1);

namespace App\Services\Academics\Content;

use App\Models\Academics\ContentItem;
use App\Models\Academics\ContentItemGroup;
use App\Models\Academics\ContentOperation;
use App\Models\Academics\ContentSource;
use App\Models\Academics\ContentVersion;
use App\Models\Academics\TeachingGroup;
use App\Models\Academics\TeachingGroupMembership;
use App\Models\Platform\School;
use App\Models\User;
use App\Policies\Academics\ContentPolicy;
use App\Services\Academics\AcademicAuthority;
use App\Services\Platform\Audit\Audit;
use App\Services\Platform\Events\ContentPublished;
use App\Services\Platform\Events\ContentUploaded;
use App\Services\Platform\Events\ContextChanged;
use App\Services\Platform\Events\Outbox;
use App\Services\Platform\Tenancy\CurrentSchool;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Crypt;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Str;

class ContentFlow
{
    public static function revisionRules(): array
    {
        return ['item_revision' => ['required', 'integer', 'min:1'], 'version_revision' => ['required', 'integer', 'min:1'], 'operation_id' => ['required', 'uuid']];
    }

    private function once(User $actor, string $kind, array $input, callable $authority, callable $write): array
    {
        $operation = Validator::make($input, ['operation_id' => ['required', 'uuid']])->validate()['operation_id'];
        $hash = hash('sha256', json_encode([$kind, $input], JSON_THROW_ON_ERROR));

        return DB::transaction(function () use ($actor, $kind, $operation, $hash, $authority, $write) {
            School::whereKey(app(CurrentSchool::class)->id())->lockForUpdate()->firstOrFail();
            $authority();
            $previous = ContentOperation::where('actor_id', $actor->id)->where('operation_id', $operation)->first();
            if ($previous !== null) {
                abort_unless(hash_equals($previous->request_hash, $hash), 409, __('This operation already has a different content change. Review before retrying.'));
                app(ContentPolicy::class)->staff($actor, (int) $previous->content_item_id, true);

                return $previous->result;
            }
            $saved = $write();
            $result = $this->receipt($saved['item'], $saved['version']);
            ContentOperation::create(['actor_id' => $actor->id, 'operation_id' => $operation, 'kind' => $kind, 'request_hash' => $hash, 'content_item_id' => $saved['item']->id, 'result' => $result]);

            return $result;
        }, 3);
    }

    private function receipt(ContentItem $item, ?ContentVersion $version): array
    {
        return ['id' => (int) $item->id, 'version_id' => $version === null ? null : (int) $version->id, 'item_revision' => (int) $item->revision, 'version_revision' => $version?->revision];
    }

    private function revisions(ContentItem $item, ContentVersion $version, array $data): void
    {
        abort_unless((int) $item->draft_version_id === (int) $version->id && $item->revision === (int) $data['item_revision'] && $version->revision === (int) $data['version_revision'], 409, __('Content changed. Reload the current draft and compare before saving.'));
    }

    private function newVersion(User $actor, ContentItem $item, ContentSource $source, array $metadata, ?ContentVersion $copy = null): ContentVersion
    {
        $old = $item->draft_version_id === null ? null : ContentVersion::find($item->draft_version_id);
        if ($old !== null && $old->published_at === null) {
            $old->update(['state' => 'superseded', 'lease_until' => null, 'revision' => $old->revision + 1]);
        }
        $ready = $copy !== null && $source->scan_state === 'clean' && $source->scanned_at?->gt(now()->subDays(7));
        $version = ContentVersion::create(['content_item_id' => $item->id, 'content_source_id' => $source->id, 'created_by' => $actor->id, 'number' => (int) $item->versions()->max('number') + 1,
            'metadata' => $metadata, 'markdown' => $copy?->markdown, 'duration_seconds' => $copy?->duration_seconds, 'language' => $copy?->language, 'state' => $ready ? 'draft' : 'queued', 'generation_id' => (string) Str::uuid(), 'revision' => 1]);
        $item->update(['draft_version_id' => $version->id, 'revision' => $item->revision + 1]);
        if (! $ready) {
            Outbox::record(new ContentUploaded(app(CurrentSchool::class)->id(), (int) $version->id, $version->generation_id));
        }

        return $version;
    }

    public function create(User $actor, array $input, UploadedFile $document): array
    {
        $metadata = app(ContentSelection::class)->normalise($actor, $input);
        $file = app(ContentFiles::class)->inspect($document);
        $operation = Validator::make($input, ['operation_id' => ['required', 'uuid']])->validate()['operation_id'];
        $written = [];
        try {
            return $this->once($actor, 'create', ['selection' => $metadata, 'file' => array_diff_key($file, ['body' => true]), 'operation_id' => $operation],
                fn () => app(ContentSelection::class)->normalise($actor, $metadata), function () use ($actor, $metadata, $file, $operation, &$written) {
                    $item = ContentItem::create(['author_id' => $actor->id, 'subject_id' => $metadata['subject_id'], 'grade_level_id' => $metadata['grade_level_id'], 'session_year_id' => $metadata['session_year_id'],
                        'operation_id' => $operation, 'request_hash' => hash('sha256', json_encode([$metadata, $file['sha256']], JSON_THROW_ON_ERROR)), 'revision' => 0, 'active' => true]);
                    $source = app(ContentFiles::class)->write($item, $actor, $file);
                    $written[] = $source;
                    $version = $this->newVersion($actor, $item, $source, $metadata);
                    Audit::record('academics.content_uploaded', $item, [], ['version_id' => (int) $version->id, 'source_id' => (int) $source->id, 'sha256' => $source->sha256]);

                    return ['item' => $item, 'version' => $version];
                });
        } finally {
            foreach ($written as $source) {
                app(ContentFiles::class)->discardUncommitted($source);
            }
        }
    }

    public function upload(User $actor, int $id, array $input, UploadedFile $document): array
    {
        $item = app(ContentPolicy::class)->staff($actor, $id, true);
        $data = Validator::make($input, self::revisionRules())->validate();
        $metadata = app(ContentSelection::class)->normalise($actor, $input, $item);
        $file = app(ContentFiles::class)->inspect($document);
        $written = [];
        try {
            return $this->once($actor, 'upload', ['id' => $id, 'selection' => $metadata, 'file' => array_diff_key($file, ['body' => true])] + $data,
                fn () => app(ContentPolicy::class)->staff($actor, $id, true), function () use ($actor, $id, $data, $metadata, $file, &$written) {
                    $item = app(ContentPolicy::class)->staff($actor, $id, true);
                    $this->revisions($item, ContentVersion::findOrFail($item->draft_version_id), $data);
                    app(ContentSelection::class)->normalise($actor, $metadata, $item);
                    $source = app(ContentFiles::class)->write($item, $actor, $file);
                    $written[] = $source;
                    $version = $this->newVersion($actor, $item, $source, $metadata);
                    Audit::record('academics.content_source_replaced', $item, [], ['version_id' => (int) $version->id, 'source_id' => (int) $source->id]);

                    return ['item' => $item, 'version' => $version];
                });
        } finally {
            foreach ($written as $source) {
                app(ContentFiles::class)->discardUncommitted($source);
            }
        }
    }

    public function save(User $actor, int $id, array $input): array
    {
        $version = app(ContentPolicy::class)->version($actor, $id, true);
        $data = Validator::make($input, self::revisionRules() + ['markdown' => ['required', 'string']])->validate();
        $metadata = app(ContentSelection::class)->normalise($actor, $input, $version->item);
        $data['markdown'] = app(ContentMarkdown::class)->clean($data['markdown']);

        return $this->once($actor, 'save', ['version_id' => $id, 'selection' => $metadata] + $data, fn () => app(ContentPolicy::class)->version($actor, $id, true), function () use ($actor, $id, $data, $metadata) {
            $v = app(ContentPolicy::class)->version($actor, $id, true);
            $item = $v->item;
            $this->revisions($item, $v, $data);
            app(ContentSelection::class)->normalise($actor, $metadata, $item);
            abort_unless(in_array($v->state, ['draft', 'published'], true), 409, __('Wait for conversion or retry the failed source before editing.'));
            if ($v->published_at !== null) {
                $v = $this->newVersion($actor, $item, $v->source, $metadata, $v);
            }
            $v->update(['metadata' => $metadata, 'markdown' => $data['markdown'], 'revision' => $v->revision + 1]);
            $item->update(['revision' => $item->revision + 1]);
            Audit::record('academics.content_draft_saved', $v, [], ['markdown_sha256' => hash('sha256', $data['markdown'])]);

            return ['item' => $item, 'version' => $v];
        });
    }

    private function previewData(User $actor, ContentVersion $v): array
    {
        $metadata = app(ContentSelection::class)->normalise($actor, $v->metadata, $v->item);
        abort_unless($v->state === 'draft' && $v->source?->scan_state === 'clean' && $v->source->scanned_at?->gt(now()->subDays(7)) && is_string($v->markdown) && $v->markdown !== '', 409, __('Convert and scan a current draft before publication.'));
        $groups = TeachingGroup::whereIn('id', $metadata['group_ids'])->orderBy('id')->get()->map(fn ($g) => [$g->id, $g->revision, $g->active, $g->subject_id, $g->grade_level_id, $g->session_year_id])->all();
        $fingerprint = hash('sha256', json_encode([$v->id, $v->revision, $v->item->revision, $metadata, hash('sha256', $v->markdown), $v->source->sha256, $v->source->scan_engine, $v->source->scanned_at->toISOString(), $groups], JSON_THROW_ON_ERROR));

        return ['id' => (int) $v->id, 'content_item_id' => (int) $v->content_item_id, 'item_revision' => $v->item->revision, 'version_revision' => $v->revision, 'selection' => $metadata, 'markdown' => $v->markdown,
            'fingerprint' => $fingerprint, 'learner_count' => TeachingGroupMembership::whereIn('teaching_group_id', $metadata['group_ids'])->whereDate('starts_on', '<=', app(AcademicAuthority::class)->today())->whereDate('ends_on', '>=', app(AcademicAuthority::class)->today())->distinct()->count('learner_id'),
            'review_required' => true, 'requires_reason' => (int) $v->item->author_id !== (int) $actor->id];
    }

    public function preview(User $actor, int $id): array
    {
        return DB::transaction(function () use ($actor, $id) {
            School::whereKey(app(CurrentSchool::class)->id())->lockForUpdate()->firstOrFail();
            $v = app(ContentPolicy::class)->version($actor, $id, true);
            abort_unless((int) $v->item->draft_version_id === $id, 409, __('Open the current draft.'));

            return $this->previewData($actor, $v);
        });
    }

    public function publish(User $actor, int $id, array $input): array
    {
        $data = Validator::make($input, self::revisionRules() + ['fingerprint' => ['required', 'string', 'regex:/^[a-f0-9]{64}$/'], 'reviewed' => ['required', 'accepted'], 'reason' => ['sometimes', 'nullable', 'string', 'min:3', 'max:2000']])->validate();

        return $this->once($actor, 'publish', ['version_id' => $id] + $data, fn () => app(ContentPolicy::class)->version($actor, $id, true), function () use ($actor, $id, $data) {
            $v = app(ContentPolicy::class)->version($actor, $id, true);
            $item = $v->item;
            $this->revisions($item, $v, $data);
            $preview = $this->previewData($actor, $v);
            abort_unless(hash_equals($preview['fingerprint'], $data['fingerprint']), 409, __('Content or its audience changed. Review the current publication preview.'));
            abort_if($preview['requires_reason'] && trim((string) ($data['reason'] ?? '')) === '', 422, __('Record why you are publishing another author’s material.'));
            $v->update(['state' => 'published', 'reviewed_by' => $actor->id, 'reviewed_at' => now(), 'review_reason' => $data['reason'] ?? null, 'published_at' => now(), 'published_fingerprint' => $data['fingerprint'], 'publish_operation_id' => $data['operation_id'], 'revision' => $v->revision + 1]);
            ContentItemGroup::where('content_item_id', $item->id)->delete();
            foreach ($preview['selection']['group_ids'] as $group) {
                ContentItemGroup::create(['content_item_id' => $item->id, 'teaching_group_id' => $group]);
            }
            $item->update(['published_version_id' => $v->id, 'notice_after_learner_id' => 0, 'notice_completed_at' => null, 'active' => true, 'revision' => $item->revision + 1]);
            Audit::record('academics.content_published', $v, [], ['fingerprint' => $data['fingerprint'], 'reviewed_by' => (int) $actor->id, 'reason_ciphertext' => $v->getRawOriginal('review_reason')]);
            Outbox::record(new ContentPublished(app(CurrentSchool::class)->id(), (int) $v->id));

            return ['item' => $item, 'version' => $v];
        });
    }

    public function retry(User $actor, int $id, array $input): array
    {
        $data = Validator::make($input, self::revisionRules())->validate();

        return $this->once($actor, 'retry', ['version_id' => $id] + $data, fn () => app(ContentPolicy::class)->version($actor, $id, true), function () use ($actor, $id, $data) {
            $v = app(ContentPolicy::class)->version($actor, $id, true);
            $item = $v->item;
            $this->revisions($item, $v, $data);
            abort_unless(in_array($v->state, ['failed', 'quarantined'], true), 409, __('This version is not awaiting a retry. Refresh its status.'));
            $v->update(['state' => 'queued', 'generation_id' => (string) Str::uuid(), 'attempt_id' => null, 'lease_until' => null, 'failure_code' => null, 'revision' => $v->revision + 1]);
            $item->update(['revision' => $item->revision + 1]);
            Outbox::record(new ContentUploaded(app(CurrentSchool::class)->id(), (int) $v->id, $v->generation_id));
            Audit::record('academics.content_retried', $v);

            return ['item' => $item, 'version' => $v];
        });
    }

    public function archive(User $actor, int $id, array $input): array
    {
        $data = Validator::make($input, ['item_revision' => ['required', 'integer', 'min:1'], 'operation_id' => ['required', 'uuid'], 'reason' => ['required', 'string', 'min:3', 'max:2000']])->validate();

        return $this->once($actor, 'archive', ['item_id' => $id] + $data, fn () => app(ContentPolicy::class)->staff($actor, $id, true), function () use ($actor, $id, $data) {
            $item = app(ContentPolicy::class)->staff($actor, $id, true);
            abort_unless($item->revision === (int) $data['item_revision'], 409, __('Content changed. Review before archiving.'));
            $item->update(['active' => false, 'revision' => $item->revision + 1]);
            Audit::record('academics.content_archived', $item, [], ['reason_ciphertext' => Crypt::encryptString($data['reason'])]);
            Outbox::record(new ContextChanged(app(CurrentSchool::class)->id(), 'academics.content_archived'));

            return ['item' => $item, 'version' => $item->draft ?? $item->published];
        });
    }
}
