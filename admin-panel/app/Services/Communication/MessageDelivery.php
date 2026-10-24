<?php

declare(strict_types=1);

namespace App\Services\Communication;

use App\Jobs\Communication\ProcessMessage;
use App\Models\Communication\Message;
use App\Models\Communication\MessageRecipient;
use App\Models\Notification;
use App\Models\Platform\School;
use App\Models\Platform\SchoolMembership;
use App\Models\User;
use App\Policies\Communication\MessagePolicy;
use App\Services\Academics\AcademicAuthority;
use App\Services\Platform\Audit\Audit;
use App\Services\Platform\Modules\ModuleGate;
use App\Services\Platform\Notifications\Notifier;
use App\Services\Platform\Tenancy\CurrentSchool;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Symfony\Component\HttpKernel\Exception\HttpExceptionInterface;

class MessageDelivery
{
    public function process(int $id): int
    {
        return DB::transaction(function () use ($id) {
            $school = app(CurrentSchool::class);
            $lockedSchool = School::whereKey($school->id())->lockForUpdate()->firstOrFail();
            if ($lockedSchool->status !== 'active' || ! app(ModuleGate::class)->allows('communication')) {
                return 0;
            }
            $message = Message::find($id);
            if ($message === null) {
                return 0;
            }
            if ($message->state === 'pending') {
                return $this->reviewers($message);
            }
            if ($message->state === 'approved') {
                if ($message->scheduled_at?->gt(now())) {
                    return 0;
                }
                if (! $this->publish($message)) {
                    return 0;
                }
            }
            if ($message->state !== 'dispatching') {
                return 0;
            }

            return $this->recipients($message);
        }, 3);
    }

    private function notice(Message $message, bool $review): Notification
    {
        $notifier = app(Notifier::class);
        $type = $review ? 'communication.approval_requested' : 'communication.message';
        $link = '/communication/messages?message='.$message->id;
        $notice = $notifier->makeNotice($type, $review ? __('Message awaiting approval') : __('New school message'),
            $review ? __('Open Messages to review a school message.') : __('Open Messages to read a school message.'), $link);
        $notice->delivery_payload = ['link' => $link, 'communication_message_id' => (int) $message->id,
            'communication_publication_id' => $review ? null : $message->publication_id,
            'communication_review_revision' => $review ? $message->revision : null];
        $notice->save();

        return $notice;
    }

    private function reviewers(Message $message): int
    {
        if ($message->review_completed_revision === $message->revision) {
            return 0;
        }
        $staffIds = SchoolMembership::active()->where('school_id', app(CurrentSchool::class)->id())->whereNotIn('role', ['Student', 'Parent'])->select('user_id');
        $batch = app(CommunicationRecipients::class)->members()->whereIn('id', $staffIds)->where('id', '>', $message->review_after_user_id)->orderBy('id')->limit(101)->get();
        $more = $batch->count() > 100;
        $batch = $batch->take(100);
        $queued = 0;
        $notice = $message->review_notification_id === null ? null : Notification::find($message->review_notification_id);
        foreach ($batch as $user) {
            if (! app(MessagePolicy::class)->canReview($user, $message)) {
                continue;
            }
            $notice ??= $this->notice($message, true);
            if (app(Notifier::class)->send($user, $notice->type, $notice->title, $notice->message, $notice->delivery_payload['link'], $notice) !== []) {
                $queued++;
            }
        }
        $message->update(['review_notification_id' => $notice?->id, 'review_after_user_id' => $batch->last()?->id ?? $message->review_after_user_id,
            'review_completed_revision' => $more ? 0 : $message->revision]);
        if ($more) {
            ProcessMessage::dispatch(app(CurrentSchool::class)->id(), (int) $message->id);
        }

        return $queued;
    }

    private function publish(Message $message): bool
    {
        try {
            $author = User::findOrFail($message->author_id);
            $approval = app(CommunicationRules::class)->effective($author)['requires_approval'];
            $recipients = app(MessagePolicy::class)->recipients($author, $message->audience, app(AcademicAuthority::class)->today());
            if ($approval) {
                $reviewer = $message->approved_by === null ? null : User::find($message->approved_by);
                abort_unless($reviewer !== null && app(MessagePolicy::class)->canReview($reviewer, $message), 403);
            }
        } catch (HttpExceptionInterface|ModelNotFoundException $error) {
            if ($error instanceof HttpExceptionInterface && ! in_array($error->getStatusCode(), [403, 404], true)) {
                throw $error;
            }
            $message->update(['state' => 'blocked', 'blocked_code' => 'authority_changed', 'revision' => $message->revision + 1]);
            Audit::record('communication.delivery_blocked', $message, [], ['code' => 'authority_changed']);

            return false;
        }
        $eligible = [];
        foreach (app(CommunicationRecipients::class)->members()->whereIn('id', array_keys($recipients))->get() as $user) {
            if (app(RecipientAccess::class)->allows($user, $recipients[$user->id])) {
                $eligible[(int) $user->id] = $recipients[$user->id];
            }
        }
        if ($eligible === []) {
            $message->update(['state' => 'blocked', 'blocked_code' => 'empty_audience', 'revision' => $message->revision + 1]);

            return false;
        }
        $message->update(['state' => 'dispatching', 'publication_id' => (string) Str::uuid(), 'published_at' => now(), 'recipient_count' => count($eligible), 'revision' => $message->revision + 1]);
        foreach ($eligible as $id => $reason) {
            MessageRecipient::create(['message_id' => $message->id, 'user_id' => $id, 'access_reason' => $reason]);
        }
        $message->update(['notification_id' => $this->notice($message, false)->id]);
        Audit::record('communication.published', $message, [], ['recipient_count' => count($eligible), 'publication_id' => $message->publication_id]);

        return true;
    }

    private function recipients(Message $message): int
    {
        $batch = MessageRecipient::where('message_id', $message->id)->where('user_id', '>', $message->delivery_after_user_id)->orderBy('user_id')->limit(101)->get();
        $more = $batch->count() > 100;
        $batch = $batch->take(100);
        $queued = 0;
        $notice = Notification::find($message->notification_id);
        if ($notice === null) {
            $notice = $this->notice($message, false);
            $message->update(['notification_id' => $notice->id]);
        }
        foreach ($batch as $row) {
            if ($row->processed_at !== null) {
                continue;
            }
            $user = User::find($row->user_id);
            $channels = $user !== null && app(RecipientAccess::class)->allows($user, $row->access_reason)
                ? app(Notifier::class)->send($user, $notice->type, $notice->title, $notice->message, $notice->delivery_payload['link'], $notice, $message->channels) : [];
            $row->update(['processed_at' => now(), 'notification_queued' => $channels !== []]);
            if ($channels !== []) {
                $queued++;
            }
        }
        $message->update(['delivery_after_user_id' => $batch->last()?->user_id ?? $message->delivery_after_user_id,
            'state' => $more ? 'dispatching' : 'sent', 'sent_at' => $more ? null : now()]);
        if ($more) {
            ProcessMessage::dispatch(app(CurrentSchool::class)->id(), (int) $message->id);
        }

        return $queued;
    }

    /** Called immediately before email/push, after the recipient intent is frozen. */
    public function allows(User $actor, string $type, array $context): bool
    {
        try {
            $id = (int) ($context['communication_message_id'] ?? 0);
            if ($type === 'communication.approval_requested') {
                $message = Message::where('state', 'pending')->find($id);

                return $message !== null && $message->revision === (int) ($context['communication_review_revision'] ?? 0)
                    && app(MessagePolicy::class)->canReview($actor, $message);
            }
            if ($type !== 'communication.message') {
                return false;
            }
            $message = app(MessagePolicy::class)->reader($actor, $id);

            return is_string($context['communication_publication_id'] ?? null)
                && hash_equals($message->publication_id, $context['communication_publication_id']);
        } catch (HttpExceptionInterface|ModelNotFoundException $error) {
            if ($error instanceof HttpExceptionInterface && ! in_array($error->getStatusCode(), [403, 404], true)) {
                throw $error;
            }

            return false;
        }
    }
}
