<?php

declare(strict_types=1);

namespace App\Services\Activities\Chat;

use App\Jobs\Platform\DispatchOutbox;
use App\Models\Activities\Chat\GroupChatPolicy;
use App\Models\User;
use App\Policies\People\ProfilePolicy;
use App\Services\Activities\ActivityOperations;
use App\Services\Platform\Audit\Audit;
use App\Services\Platform\Events\ContextChanged;
use App\Services\Platform\Events\Outbox;
use App\Services\Platform\Tenancy\CurrentSchool;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Validator;
use Throwable;

class GroupChatRules
{
    public const DEFAULTS = ['staff_learners' => true, 'staff_guardians' => true, 'staff_staff' => true,
        'learner_shared_replies' => false, 'guardian_shared_replies' => false];

    public function current(): array
    {
        $row = GroupChatPolicy::first();
        $result = ['revision' => (int) ($row?->revision ?? 0)];
        foreach (self::DEFAULTS as $key => $default) {
            $result[$key] = (bool) ($row?->$key ?? $default);
        }

        return $result;
    }

    public function authorize(User $actor, bool $write = false): void
    {
        $fresh = app(GroupChatAccess::class)->identity($actor, $write);
        abort_unless(app(ProfilePolicy::class)->permission($fresh, 'chat-settings'), 403);
    }

    public function read(User $actor): array
    {
        $this->authorize($actor);

        return $this->current();
    }

    public function save(User $actor, array $input): array
    {
        $this->authorize($actor, true);
        $rules = ['operation_id' => ['required', 'uuid'], 'revision' => ['required', 'integer', 'min:0']];
        foreach (self::DEFAULTS as $key => $default) {
            $rules[$key] = ['required', 'boolean'];
        }
        $data = Validator::make($input, $rules)->validate();

        return app(ActivityOperations::class)->once($actor, 'chat.policy', $data,
            fn () => $this->authorize($actor, true), function () use ($actor, $data) {
                $old = $this->current();
                abort_unless($old['revision'] === (int) $data['revision'], 409, 'Refresh the group chat policy before saving.');
                $row = GroupChatPolicy::firstOrNew();
                foreach (self::DEFAULTS as $key => $default) {
                    $row->$key = (bool) $data[$key];
                }
                $row->revision = $old['revision'] + 1;
                $row->save();
                $this->authorize($actor, true);
                Audit::record('activity.chat_policy_changed', $row, $old, $this->current());
                Outbox::record(new ContextChanged(app(CurrentSchool::class)->id(), 'group_chat_policy'));
                DB::afterCommit(static function () {
                    try {
                        DispatchOutbox::dispatch();
                    } catch (Throwable $error) {
                        Log::warning('group_chat_policy_refresh_pending', ['exception' => $error::class]);
                    }
                });

                return $this->current();
            });
    }

    public function maxCharacters(): int
    {
        return max(1, min(10000, (int) schoolSetting('max_characters_in_text_message', 500)));
    }

    public function retentionDays(): int
    {
        return max(1, min(365, (int) schoolSetting('automatically_messages_removed_days', 30)));
    }
}
