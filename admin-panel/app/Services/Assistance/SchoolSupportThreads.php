<?php

declare(strict_types=1);

namespace App\Services\Assistance;

use App\Models\Assistance\SupportMessage;
use App\Models\Assistance\SupportThread;
use App\Models\User;
use App\Services\Platform\Tenancy\CurrentSchool;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Str;

/** Explicit Parent-to-office handoff, independent of external inference. */
class SchoolSupportThreads
{
    private function input(array $input): array
    {
        return Validator::make($input, ['operation_id' => ['required', 'uuid'], 'text' => ['required', 'string', 'max:4000'],
            'language' => ['required', 'string', 'regex:/^[a-z]{2,3}(?:-[A-Za-z]{2,8})?$/D', 'max:16']])->validate();
    }

    private function append(User $actor, SupportThread $thread, array $input, bool $office): SupportMessage
    {
        $text = trim($input['text']);
        abort_if($text === '' || strlen($text) > 12000, 422);

        return SupportMessage::create(['id' => (string) Str::uuid(), 'thread_id' => $thread->id, 'author_id' => $actor->id,
            'author_kind' => $office ? 'office' : 'parent', 'text' => $text, 'content_hash' => hash('sha256', $text),
            'language' => $input['language'], 'sent_at' => now()->utc()]);
    }

    public function open(User $actor, int $learner, array $input): array
    {
        $data = $this->input($input);

        return app(AssistanceOperations::class)->once($actor, 'thread.open', ['learner_id' => $learner] + $data,
            fn () => app(AssistanceAccess::class)->family($actor, $learner, true), function () use ($actor, $learner, $data): array {
                abort_if(SupportThread::where('parent_user_id', $actor->id)->where('state', 'open')->where('expires_at', '>', now()->utc())->count() >= 20, 429, 'Close or resolve an existing school-support thread.');
                $thread = SupportThread::create(['id' => (string) Str::uuid(), 'parent_user_id' => $actor->id, 'learner_id' => $learner,
                    'assigned_user_id' => null, 'state' => 'open', 'revision' => 1, 'expires_at' => now()->utc()->addDays(90)]);
                $message = $this->append($actor, $thread, $data, false);
                app(AssistanceNotices::class)->changed($thread, $actor, $message->id);

                return ['id' => $thread->id, 'message_id' => $message->id, 'revision' => $thread->revision, 'state' => $thread->state];
            });
    }

    /** Office queue exposes opaque metadata; claim before decrypting a message. */
    public function officeQueue(User $actor, int $page = 1): array
    {
        $fresh = app(AssistanceAccess::class)->office($actor);
        $rows = SupportThread::whereNull('purged_at')->where('expires_at', '>', now()->utc())
            ->orderByDesc('created_at')->orderBy('id')->paginate(20, ['id', 'state', 'revision', 'assigned_user_id', 'created_at'], 'page', max(1, $page));
        app(AssistanceAccess::class)->office($actor);

        return ['page' => $rows->currentPage(), 'last_page' => $rows->lastPage(), 'items' => $rows->map(fn ($row) => ['id' => $row->id,
            'state' => $row->state, 'revision' => $row->revision, 'assigned_to_reader' => (int) $row->assigned_user_id === (int) $actor->id,
            'created_at' => $row->created_at->toIso8601String()])->all()];
    }

    public function familyList(User $actor, int $learner, int $page = 1): array
    {
        app(AssistanceAccess::class)->family($actor, $learner);
        $rows = SupportThread::where('parent_user_id', $actor->id)->where('learner_id', $learner)
            ->whereNull('purged_at')->where('expires_at', '>', now()->utc())->orderByDesc('created_at')->orderBy('id')
            ->paginate(20, ['id', 'state', 'revision', 'created_at'], 'page', max(1, $page));
        app(AssistanceAccess::class)->family($actor, $learner);

        return ['page' => $rows->currentPage(), 'last_page' => $rows->lastPage(), 'items' => $rows->map(fn ($row) => ['id' => $row->id,
            'state' => $row->state, 'revision' => $row->revision, 'created_at' => $row->created_at->toIso8601String()])->all()];
    }

    public function message(User $actor, string $id, bool $office = false): SupportMessage
    {
        app(AssistanceAccess::class)->actor($actor);
        $meta = SupportMessage::select('id', 'thread_id', 'purged_at')->findOrFail($id);
        app(AssistanceAccess::class)->thread($actor, $meta->thread_id, false, $office);
        abort_unless($meta->purged_at === null, 404);

        return SupportMessage::findOrFail($id);
    }

    public function claim(User $actor, string $id, array $input): array
    {
        $input = Validator::make($input, ['operation_id' => ['required', 'uuid'], 'expected_revision' => ['required', 'integer', 'min:1']])->validate();

        return app(AssistanceOperations::class)->once($actor, 'thread.claim', ['id' => $id] + $input,
            function () use ($actor, $id): void {
                app(AssistanceAccess::class)->office($actor, true);
                $thread = SupportThread::findOrFail($id);
                abort_unless($thread->purged_at === null && $thread->expires_at->isFuture()
                    && ($thread->assigned_user_id === null || (int) $thread->assigned_user_id === (int) $actor->id), 404);
                app(AssistanceAccess::class)->family(User::findOrFail($thread->parent_user_id), (int) $thread->learner_id);
            }, function () use ($actor, $id, $input): array {
                $thread = SupportThread::findOrFail($id);
                abort_unless($thread->state === 'open' && $thread->revision === (int) ($input['expected_revision'] ?? 0), 409);
                $thread->update(['assigned_user_id' => $actor->id, 'revision' => $thread->revision + 1]);
                app(AssistanceNotices::class)->changed($thread, $actor);

                return ['id' => $id, 'revision' => $thread->revision, 'state' => $thread->state];
            });
    }

    /** Reasoned, CAS-bound ownership handoff; no message body is copied. */
    public function reassign(User $actor, string $id, array $input): array
    {
        $data = Validator::make($input, ['operation_id' => ['required', 'uuid'], 'expected_revision' => ['required', 'integer', 'min:1'],
            'assigned_user_id' => ['required', 'integer', 'min:1'], 'reason' => ['required', 'string', 'max:1000']])->validate();
        $authorize = function () use ($actor, $id, $data): void {
            app(AssistanceAccess::class)->office($actor, true);
            app(AssistanceAccess::class)->office(User::findOrFail($data['assigned_user_id']), true);
            $thread = SupportThread::findOrFail($id);
            abort_unless($thread->purged_at === null && $thread->expires_at->isFuture(), 404);
            app(AssistanceAccess::class)->family(User::findOrFail($thread->parent_user_id), (int) $thread->learner_id);
        };

        return app(AssistanceOperations::class)->once($actor, 'thread.reassign', ['id' => $id] + $data, $authorize,
            function () use ($actor, $id, $data): array {
                $row = SupportThread::findOrFail($id);
                abort_unless($row->state === 'open' && $row->revision === (int) $data['expected_revision'], 409);
                $row->update(['assigned_user_id' => (int) $data['assigned_user_id'], 'revision' => $row->revision + 1]);
                app(AssistanceNotices::class)->changed($row, $actor);

                // The immutable office operation receipt keeps the reason
                // encrypted. Parent thread reads never return staff reasons.
                return ['id' => $id, 'revision' => $row->revision, 'state' => $row->state,
                    'assigned_user_id' => (int) $row->assigned_user_id, 'reason' => trim($data['reason'])];
            });
    }

    public function read(User $actor, string $id, bool $office = false, int $page = 1): array
    {
        $thread = app(AssistanceAccess::class)->thread($actor, $id, false, $office);
        $rows = SupportMessage::where('thread_id', $id)->whereNull('purged_at')->orderByDesc('sent_at')->orderByDesc('id')->paginate(20, ['*'], 'page', max(1, $page));
        app(AssistanceAccess::class)->thread($actor, $id, false, $office);

        return ['id' => $id, 'revision' => $thread->revision, 'state' => $thread->state, 'learner_id' => (int) $thread->learner_id,
            'expires_at' => $thread->expires_at->toIso8601String(), 'page' => $rows->currentPage(), 'last_page' => $rows->lastPage(),
            'messages' => $rows->map(fn ($row) => ['id' => $row->id, 'author_kind' => $row->author_kind, 'text' => $row->text,
                'language' => $row->language, 'sent_at' => $row->sent_at->toIso8601String()])->all()];
    }

    public function reply(User $actor, string $id, array $input, bool $office = false): array
    {
        $data = $this->input($input);

        return app(AssistanceOperations::class)->once($actor, 'thread.reply', ['id' => $id, 'office' => $office] + $data,
            function () use ($actor, $id, $office): void {
                app(AssistanceAccess::class)->actor($actor, true);
                app(AssistanceAccess::class)->thread($actor, $id, false, $office);
            }, function () use ($actor, $id, $data, $office): array {
                $thread = app(AssistanceAccess::class)->thread($actor, $id, true, $office);
                abort_if(SupportMessage::where('thread_id', $id)->count() >= 200, 422, 'This thread reached its bounded message limit.');
                $row = $this->append($actor, $thread, $data, $office);
                $thread->update(['revision' => $thread->revision + 1]);
                app(AssistanceNotices::class)->changed($thread, $actor, $row->id);

                return ['id' => $id, 'message_id' => $row->id, 'revision' => $thread->revision, 'state' => $thread->state];
            });
    }

    public function close(User $actor, string $id, array $input, bool $office = false): array
    {
        $input = Validator::make($input, ['operation_id' => ['required', 'uuid'], 'expected_revision' => ['required', 'integer', 'min:1']])->validate();

        return app(AssistanceOperations::class)->once($actor, 'thread.close', ['id' => $id, 'office' => $office] + $input,
            function () use ($actor, $id, $office): void {
                app(AssistanceAccess::class)->actor($actor, true);
                app(AssistanceAccess::class)->thread($actor, $id, false, $office);
            }, function () use ($actor, $id, $input, $office): array {
                $thread = app(AssistanceAccess::class)->thread($actor, $id, true, $office);
                abort_unless($thread->revision === (int) ($input['expected_revision'] ?? 0), 409);
                $thread->update(['state' => 'closed', 'revision' => $thread->revision + 1, 'closed_at' => now()->utc()]);
                app(AssistanceNotices::class)->changed($thread, $actor);

                return ['id' => $id, 'revision' => $thread->revision, 'state' => 'closed'];
            });
    }

    /** Tenant job: reads expire immediately, then bounded physical erasure follows. */
    public function purgeExpired(): int
    {
        $school = app(CurrentSchool::class);
        abort_unless($school->isSet() && ! $school->isAcrossSchools() && ! $school->isImpersonating(), 403);
        $ids = SupportThread::whereNull('purged_at')->where('expires_at', '<=', now()->utc())->orderBy('id')->limit(200)->pluck('id');
        foreach ($ids as $id) {
            DB::transaction(function () use ($id): void {
                $row = SupportThread::lockForUpdate()->findOrFail($id);
                if ($row->purged_at !== null || $row->expires_at->isFuture()) {
                    return;
                }
                SupportMessage::where('thread_id', $id)->whereNull('purged_at')->update(['text' => null, 'purged_at' => now()->utc()]);
                $row->update(['state' => 'closed', 'purged_at' => now()->utc()]);
            });
        }

        return $ids->count();
    }
}
