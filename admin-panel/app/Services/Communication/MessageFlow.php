<?php

declare(strict_types=1);

namespace App\Services\Communication;

use App\Jobs\Communication\ProcessMessage;
use App\Models\Communication\Message;
use App\Models\User;
use App\Policies\Communication\MessagePolicy;
use App\Services\Academics\AcademicAuthority;
use App\Services\Academics\Content\ContentMarkdown;
use App\Services\Platform\Audit\Audit;
use App\Services\Platform\Tenancy\CurrentSchool;
use Carbon\CarbonImmutable;
use Illuminate\Validation\ValidationException;

class MessageFlow
{
    public static function revisionRules(): array
    {
        return ['revision' => ['required', 'integer', 'min:1'], 'operation_id' => ['required', 'uuid']];
    }

    public function scheduled(?string $value): ?CarbonImmutable
    {
        if ($value === null || $value === '') {
            return null;
        }
        $timezone = app(CurrentSchool::class)->school()->timezone;
        try {
            $date = CarbonImmutable::createFromFormat('!Y-m-d\TH:i', $value, $timezone);
            if ($date === false || $date->format('Y-m-d\TH:i') !== $value) {
                throw new \InvalidArgumentException;
            }
        } catch (\Throwable) {
            throw ValidationException::withMessages(['scheduled_local' => __('Choose a valid date and time in the school timezone.')]);
        }

        return $date->utc();
    }

    private function receipt(Message $message): array
    {
        return ['id' => (int) $message->id, 'revision' => $message->revision, 'state' => $message->state];
    }

    private function revision(Message $message, array $input): void
    {
        abort_unless($message->revision === (int) $input['revision'], 409, __('The message changed. Refresh and compare before saving.'));
    }

    public function save(User $actor, ?int $id, array $data): array
    {
        $policy = app(MessagePolicy::class);
        $authority = function () use ($actor, $id) {
            app(CommunicationRules::class)->staff($actor);
            if ($id !== null) {
                app(MessagePolicy::class)->staff($actor, $id, true);
            }
        };

        return app(CommunicationOperations::class)->once($actor, 'draft', ['id' => $id] + $data, $authority, function () use ($actor, $id, $data, $policy) {
            $message = $id === null ? new Message : $policy->staff($actor, $id, true);
            if ($id === null) {
                abort_unless((int) $data['revision'] === 0, 409);
            } else {
                $this->revision($message, $data);
            }
            abort_unless($message->published_at === null && $message->state !== 'withdrawn', 409, __('Published messages are immutable. Prepare a new message.'));
            abort_if(strlen($data['markdown']) > 65536, 422, __('Keep the message within 64 KiB.'));
            $definition = $policy->snapshot($actor, $data['audience'], app(AcademicAuthority::class)->today());
            $markdown = app(ContentMarkdown::class)->clean($data['markdown']);
            abort_if(strlen($markdown) > 65536, 422, __('Keep the message within 64 KiB.'));
            $title = trim(strip_tags($data['title']));
            abort_if($title === '', 422, __('Enter a readable message subject.'));
            $schedule = $this->scheduled($data['scheduled_local'] ?? null);
            $channels = $data['channels'];
            sort($channels);
            $message->fill(['author_id' => $actor->id, 'title' => $title, 'markdown' => $markdown, 'audience' => $definition,
                'channels' => $channels, 'scheduled_at' => $schedule, 'state' => 'draft', 'requires_approval' => app(CommunicationRules::class)->effective($actor)['requires_approval'],
                'submitted_at' => null, 'approved_at' => null, 'approved_by' => null, 'decision_reason' => null, 'blocked_code' => null,
                'review_notification_id' => null, 'review_after_user_id' => 0, 'review_completed_revision' => 0, 'revision' => (int) ($message->revision ?? 0) + 1])->save();
            Audit::record('communication.draft_saved', $message, [], ['revision' => $message->revision, 'body_sha256' => hash('sha256', $markdown)]);

            return $this->receipt($message);
        });
    }

    public function fingerprint(Message $message, array $recipients, bool $approval): string
    {
        return hash('sha256', json_encode([$message->id, $message->revision, $message->title, $message->markdown, $message->audience, $message->channels, $message->scheduled_at?->toIso8601String(), $approval, $recipients], JSON_THROW_ON_ERROR));
    }

    public function preview(User $actor, int $id): array
    {
        $message = app(MessagePolicy::class)->staff($actor, $id);
        $author = User::findOrFail($message->author_id);
        $recipients = app(MessagePolicy::class)->recipients($author, $message->audience, app(AcademicAuthority::class)->today());
        $approval = app(CommunicationRules::class)->effective($author)['requires_approval'];

        return ['id' => $id, 'revision' => $message->revision, 'recipient_count' => count($recipients), 'requires_approval' => $approval,
            'fingerprint' => $this->fingerprint($message, $recipients, $approval), 'html' => app(ContentMarkdown::class)->html($message->markdown),
            'channels' => $message->channels, 'scheduled_at' => $message->scheduled_at?->toIso8601String(), 'timezone' => app(CurrentSchool::class)->school()->timezone];
    }

    public function submit(User $actor, int $id, array $data): array
    {
        return app(CommunicationOperations::class)->once($actor, 'submit', ['id' => $id] + $data, fn () => app(MessagePolicy::class)->staff($actor, $id, true), function () use ($actor, $id, $data) {
            $message = app(MessagePolicy::class)->staff($actor, $id, true);
            $this->revision($message, $data);
            abort_unless(in_array($message->state, ['draft', 'rejected', 'blocked'], true) && $message->published_at === null, 409);
            $preview = $this->preview($actor, $id);
            abort_unless(hash_equals($preview['fingerprint'], $data['fingerprint']), 409, __('The audience or message changed. Preview again before sending.'));
            abort_unless($preview['recipient_count'] > 0, 422, __('The audience has no current recipients.'));
            abort_if($message->scheduled_at !== null && $message->scheduled_at->lte(now()), 422, __('Choose a future school time or Send now.'));
            $message->update(['state' => $preview['requires_approval'] ? 'pending' : 'approved', 'submitted_at' => now(), 'requires_approval' => $preview['requires_approval'],
                'approved_by' => null, 'approved_at' => null, 'decision_reason' => null, 'blocked_code' => null, 'review_notification_id' => null, 'review_after_user_id' => 0, 'review_completed_revision' => 0, 'revision' => $message->revision + 1]);
            ProcessMessage::dispatch(app(CurrentSchool::class)->id(), $id);
            Audit::record('communication.submitted', $message, [], ['revision' => $message->revision, 'approval' => $message->requires_approval]);

            return $this->receipt($message);
        });
    }

    public function decide(User $actor, int $id, array $data): array
    {
        $authority = function () use ($actor, $id) {
            $m = app(MessagePolicy::class)->staff($actor, $id);
            abort_unless(app(MessagePolicy::class)->canReview($actor, $m), 404);
        };

        return app(CommunicationOperations::class)->once($actor, 'decision', ['id' => $id] + $data, $authority, function () use ($actor, $id, $data) {
            $message = app(MessagePolicy::class)->staff($actor, $id);
            $this->revision($message, $data);
            abort_unless($message->state === 'pending', 409);
            $preview = $this->preview($actor, $id);
            abort_unless(hash_equals($preview['fingerprint'], $data['fingerprint']), 409, __('The audience or message changed. Preview again before deciding.'));
            $approved = $data['decision'] === 'approve';
            $message->update(['state' => $approved ? 'approved' : 'rejected', 'approved_by' => $approved ? $actor->id : null, 'approved_at' => $approved ? now() : null,
                'decision_reason' => $data['reason'], 'revision' => $message->revision + 1]);
            if ($approved) {
                ProcessMessage::dispatch(app(CurrentSchool::class)->id(), $id);
            }
            Audit::record($approved ? 'communication.approved' : 'communication.rejected', $message, [], ['revision' => $message->revision]);

            return $this->receipt($message);
        });
    }

    public function withdraw(User $actor, int $id, array $data): array
    {
        return app(CommunicationOperations::class)->once($actor, 'withdraw', ['id' => $id] + $data, fn () => app(MessagePolicy::class)->staff($actor, $id), function () use ($actor, $id, $data) {
            $m = app(MessagePolicy::class)->staff($actor, $id);
            $this->revision($m, $data);
            abort_if($m->state === 'withdrawn', 409);
            $m->update(['state' => 'withdrawn', 'withdrawn_at' => now(), 'withdrawal_reason' => $data['reason'], 'revision' => $m->revision + 1]);
            Audit::record('communication.withdrawn', $m, [], ['revision' => $m->revision]);

            return $this->receipt($m);
        });
    }
}
