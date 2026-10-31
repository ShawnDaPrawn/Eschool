<?php

declare(strict_types=1);

namespace App\Services\Platform\Notifications;

use App\Jobs\Platform\DispatchOutbox;
use App\Mail\Platform\NotificationMail;
use App\Models\Notification;
use App\Models\Platform\SchoolMembership;
use App\Models\SessionYear;
use App\Models\User;
use App\Models\UserNotification;
use App\Services\Growth\ProgressNotices;
use App\Services\MailService;
use App\Services\Pastoral\CoverNotices;
use App\Services\Platform\Events\NotificationRequested;
use App\Services\Platform\Events\Outbox;
use App\Services\Platform\Tenancy\CurrentSchool;
use App\Services\Transfers\TransferNotices;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use RuntimeException;
use Throwable;

/** Checks school membership, module, rules and choice before queueing. */
class Notifier
{
    public function __construct(private readonly NotificationRules $rules) {}

    /** Durable recipient intent; the worker checks each channel before delivery. */
    public function assign(Notification $notice, ?iterable $userIds): void
    {
        abort_unless((int) $notice->school_id === app(CurrentSchool::class)->id(), 404);
        $ids = $userIds === null ? null : collect($userIds)->filter(fn ($id) => is_numeric($id) && (int) $id > 0)->map(fn ($id) => (int) $id)->unique()->values()->all();
        if ($ids === []) {
            return;
        }
        DB::transaction(fn () => Outbox::record(new NotificationRequested(app(CurrentSchool::class)->id(), (int) $notice->id, $ids)));
        // Queue acceleration is best effort. If it fails, the durable event
        // remains for the every-minute scheduler, and a completed payment is
        // not reported as failed merely because its notice could not queue.
        $accelerate = static function () {
            try {
                DispatchOutbox::dispatch();
            } catch (Throwable $error) {
                Log::error('notification_fanout_acceleration_failed', ['exception' => get_class($error)]);
            }
        };
        // Even the synchronous driver must wait for the real outer commit.
        // Delivery may otherwise escape a later authorization refusal/rollback.
        if (DB::transactionLevel() === 0) {
            $accelerate();
        } else {
            DB::afterCommit($accelerate);
        }
    }

    public function assignRows(array $rows): void
    {
        foreach (collect($rows)->groupBy('notification_id') as $id => $links) {
            $notice = Notification::query()->findOrFail($id);
            $this->assign($notice, $links->pluck('user_id')->all());
        }
    }

    private function eligible(User $user): bool
    {
        return ! $user->trashed() && (int) $user->status === 1 && SchoolMembership::query()->active()
            ->where('school_id', app(CurrentSchool::class)->id())->where('user_id', $user->id)->exists();
    }

    /** Each allowed channel is claimed independently, so retrying a fanout never repeats earlier recipients. */
    public function send(User $user, string $type, string $title, string $body, ?string $link = null, ?Notification $notice = null, ?array $channels = null): array
    {
        if (! app(PrivateNoticeScopes::class)->permitsDelivery($user, $notice?->type ?? $type, $notice?->delivery_payload ?? [])) {
            return [];
        }
        if (str_starts_with($type, 'transfer.') && ! app(TransferNotices::class)->allows($user, $type, $notice?->delivery_payload ?? [])) {
            return [];
        }
        if (str_starts_with($type, 'progress.') && ! app(ProgressNotices::class)->allows($user, $type, $notice?->delivery_payload ?? [])) {
            return [];
        }
        if (str_starts_with($type, 'pastoral.cover') && ! app(CoverNotices::class)->allows($user, $type, $notice?->delivery_payload ?? [])) {
            return [];
        }
        if (! $this->eligible($user) || ! $this->rules->offered($user, $type)) {
            return [];
        }
        $link ??= NotificationTypes::link($type);
        // Links are app paths. Never turn a caller-supplied URL into an open
        // redirect in a trusted school message or include a foreign origin.
        if (! str_starts_with($link, '/') || str_starts_with($link, '//') || str_contains($link, '\\') || preg_match('/[\r\n]/', $link)) {
            throw new RuntimeException('A notification link must be an app path.');
        }
        $notice ??= $this->makeNotice($type, $title, $body, $link);
        abort_unless((int) $notice->school_id === app(CurrentSchool::class)->id(), 404);
        $sent = [];
        foreach (NotificationRules::CHANNELS as $channel) {
            if (($channels !== null && ! in_array($channel, $channels, true) && ! $this->rules->mandatory($user, $type, $channel)) || ! $this->rules->allows($user, $type, $channel)) {
                continue;
            }
            if ($channel === 'email' && filter_var($user->email, FILTER_VALIDATE_EMAIL) === false) {
                continue;
            }
            if ($channel === 'push' && app(PushDevices::class)->destinations($user) === []) {
                continue;
            }
            DB::transaction(function () use ($user, $type, $title, $body, $link, $notice, $channel, &$sent) {
                $claim = DB::table('notification_dispatches')->insertOrIgnore([
                    'school_id' => app(CurrentSchool::class)->id(), 'notification_id' => $notice->id,
                    'user_id' => $user->id, 'channel' => $channel, 'created_at' => now(), 'updated_at' => now(),
                ]);
                if ($claim === 0) {
                    return;
                }
                if ($channel === 'in_app') {
                    UserNotification::query()->firstOrCreate(['notification_id' => $notice->id, 'user_id' => $user->id]);
                } elseif ($channel === 'push') {
                    $this->push($user, $notice->type, $title, $body, array_replace($notice->delivery_payload ?? [], [
                        'type' => $notice->type, 'title' => $title, 'body' => $body, 'link' => $link,
                        'image' => $notice->image ? url(Storage::url($notice->image)) : ($notice->delivery_payload['image'] ?? ''),
                    ]));
                } else {
                    $school = app(CurrentSchool::class)->school();
                    $mailBody = $user->email_verified_at === null ? __('Open NavigateEd to view this notice.') : $body;
                    $mailBase = in_array($type, array_merge(['communication.approval_requested', 'pastoral.cover_assigned', 'progress.staff_band_changed'], (array) config('private_notices.staff_types', [])), true) ? config('app.url') : config('identity.portal_url');
                    $mail = new NotificationMail($user->email, $type, $user->email_verified_at === null ? __('School notification') : $title, $mailBody, rtrim((string) $mailBase, '/').$link, (string) $school?->name, $notice->delivery_payload ?? []);
                    if (! MailService::queue($mail, (int) $user->id)) {
                        throw new RuntimeException('A notification email could not be queued.');
                    }
                }
                $sent[] = $channel;
            });
        }

        return $sent;
    }

    public function makeNotice(string $type, string $title, string $body, string $link): Notification
    {
        $year = SessionYear::query()->find((int) schoolSetting('session_year'));
        $notice = new Notification;
        $notice->forceFill([
            'title' => Str::limit($title, 128, ''), 'message' => Str::limit($body, 128, ''), 'type' => $type,
            'send_to' => 2, 'delivery_payload' => ['link' => $link], 'date' => now(), 'session_year_id' => $year?->id,
            'is_custom' => 0, 'preferences_enforced' => true,
        ])->save();

        return $notice;
    }

    private function push(User $user, string $deliveryType, string $title, string $body, array $data): void
    {
        $data['school_id'] = (string) app(CurrentSchool::class)->id();
        $data['click_action'] ??= 'FLUTTER_NOTIFICATION_CLICK';
        $data['notice_type'] = NotificationTypes::legacy($deliveryType);
        $payload = array_map(fn ($value) => $value === null ? '' : (is_string($value) ? $value : (is_scalar($value) ? (string) $value : (json_encode($value) ?: ''))), $data);
        // One job per signed-in device, so a retry never repeats a message
        // another device already received.
        foreach (app(PushDevices::class)->destinations($user) as $device) {
            $message = ['token' => $device['token'], 'data' => $payload];
            if ($device['platform'] === 'ios') {
                $message['notification'] = ['title' => $title, 'body' => $body];
            }
            PushQueue::toDevice($deliveryType, (int) $user->id, $device['token'], ['message' => $message]);
        }
    }
}
