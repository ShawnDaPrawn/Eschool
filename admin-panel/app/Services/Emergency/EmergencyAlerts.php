<?php

declare(strict_types=1);

namespace App\Services\Emergency;

use App\Models\Emergency\EmergencyAlert;
use App\Models\Emergency\EmergencyDrill;
use App\Models\Notification;
use App\Models\Platform\SchoolMembership;
use App\Models\User;
use App\Services\Platform\Audit\Audit;
use App\Services\Platform\Notifications\Notifier;
use App\Services\Platform\Tenancy\CurrentSchool;
use Illuminate\Support\Str;

/**
 * Critical alerts and lockdowns (Action Plan 5.8). A named role prepares the
 * alert, then confirms it by typing its confirmation word: only then is it
 * sent, as a safety.critical notice to every member of staff and, unless it
 * is for staff only, every guardian. Learners are never sent it. The notice
 * goes by every channel (push, email, in-app, plus SMS and WhatsApp where the
 * school switched them on) and is never held for quiet hours. A drill says so
 * in its title and first words, whatever the sender typed. The all-clear goes
 * to the same people. Every step is in the audit trail with who did it.
 */
class EmergencyAlerts
{
    public const TYPE = 'safety.critical';

    public const KINDS = ['lockdown', 'evacuation', 'shelter', 'critical'];

    public const AUDIENCES = ['everyone', 'staff'];

    /** A prepared alert must be confirmed within this many minutes, or prepared again. */
    public const CONFIRM_MINUTES = 10;

    public static function rules(string $action): array
    {
        $operation = ['operation_id' => ['required', 'uuid']];
        $revision = ['revision' => ['required', 'integer', 'min:1']];

        return match ($action) {
            'prepare' => $operation + ['kind' => ['required', 'string', 'in:'.implode(',', self::KINDS)], 'audience' => ['required', 'string', 'in:'.implode(',', self::AUDIENCES)],
                'drill' => ['required', 'boolean'], 'drill_id' => ['nullable', 'uuid'], 'title' => ['nullable', 'string', 'max:100'], 'message' => ['nullable', 'string', 'max:1000', 'required_if:kind,critical']],
            'confirm' => $operation + $revision + ['confirmation' => ['required', 'string', 'max:20']],
            'all_clear' => $operation + $revision + ['message' => ['nullable', 'string', 'max:1000']],
            'cancel' => $operation + $revision,
        };
    }

    /** The word the sender types to confirm: the kind, or DRILL for a drill. */
    public static function confirmationWord(EmergencyAlert $alert): string
    {
        return $alert->is_drill ? 'DRILL' : match ($alert->kind) {
            'lockdown' => 'LOCKDOWN', 'evacuation' => 'EVACUATE', 'shelter' => 'SHELTER', default => 'ALERT',
        };
    }

    public static function label(string $kind): string
    {
        return match ($kind) {
            'lockdown' => __('Lockdown'), 'evacuation' => __('Evacuation'), 'shelter' => __('Shelter in place'), default => __('Emergency alert'),
        };
    }

    public static function defaultMessage(string $kind): string
    {
        return match ($kind) {
            'lockdown' => __('The school is in lockdown. Staff: lock doors, keep everyone inside and away from windows, and wait for the all-clear. Families: please do not come to the school or phone it. We will message you as soon as it is safe.'),
            'evacuation' => __('Everyone is leaving the buildings for the assembly point. Families: please do not come to the school until we message you.'),
            'shelter' => __('Everyone is staying indoors. Families: please do not come to the school until we message you.'),
            default => '',
        };
    }

    public function summary(EmergencyAlert $alert): array
    {
        return [
            'id' => $alert->id, 'kind' => $alert->kind, 'audience' => $alert->audience, 'drill' => $alert->is_drill, 'status' => $alert->status,
            'title' => $alert->title, 'message' => $alert->message, 'revision' => $alert->revision, 'drill_id' => $alert->drill_id,
            'prepared_by' => (int) $alert->prepared_by, 'prepared_at' => $alert->prepared_at->toIso8601String(),
            'confirmed_by' => $alert->confirmed_by === null ? null : (int) $alert->confirmed_by, 'activated_at' => $alert->activated_at?->toIso8601String(),
            'all_clear_by' => $alert->all_clear_by === null ? null : (int) $alert->all_clear_by, 'all_clear_at' => $alert->all_clear_at?->toIso8601String(),
            'all_clear_message' => $alert->all_clear_message, 'cancelled_at' => $alert->cancelled_at?->toIso8601String(),
            'recipient_count' => $alert->recipient_count,
            'confirmation_word' => $alert->status === 'pending' ? self::confirmationWord($alert) : null,
            'confirm_by' => $alert->status === 'pending' ? $alert->prepared_at->addMinutes(self::CONFIRM_MINUTES)->toIso8601String() : null,
        ];
    }

    /** Step one: nothing is sent yet. */
    public function prepare(User $actor, array $input): array
    {
        $data = EmergencyOperations::input($input, self::rules('prepare'));
        $authorize = fn () => app(EmergencyAccess::class)->alerter($actor);

        return app(EmergencyOperations::class)->once($actor, 'alert.prepare', $data, $authorize, function () use ($actor, $data) {
            if (($data['drill_id'] ?? null) !== null) {
                abort_unless($data['drill'], 422, 'Only a drill alert can belong to the drill log.');
                $drill = EmergencyDrill::findOrFail($data['drill_id']);
                abort_unless($drill->status === 'planned' && $drill->alert_id === null, 409, 'This drill already has an alert or is finished.');
            }
            $title = trim((string) ($data['title'] ?? '')) ?: self::label($data['kind']);
            $message = trim((string) ($data['message'] ?? '')) ?: self::defaultMessage($data['kind']);
            abort_if($message === '', 422, 'Write the message for this alert.');
            $alert = EmergencyAlert::create(['kind' => $data['kind'], 'audience' => $data['audience'], 'is_drill' => $data['drill'], 'status' => 'pending',
                'title' => Str::limit($title, 100, ''), 'message' => $message, 'drill_id' => $data['drill_id'] ?? null, 'prepared_by' => $actor->id, 'prepared_at' => now()->utc(), 'revision' => 1]);
            EmergencyOperations::event($actor, 'alert.prepared', $alert->id, $alert->id, ['kind' => $alert->kind, 'audience' => $alert->audience, 'drill' => $alert->is_drill]);

            return $this->summary($alert);
        });
    }

    /** Step two: the sender types the confirmation word, and the alert goes out once. */
    public function confirm(User $actor, string $id, array $input): array
    {
        $data = EmergencyOperations::input($input, self::rules('confirm'));
        $authorize = function () use ($actor, $id) {
            app(EmergencyAccess::class)->alerter($actor);
            EmergencyAlert::findOrFail($id);
        };

        return app(EmergencyOperations::class)->once($actor, 'alert.confirm', ['alert_id' => $id] + $data, $authorize, function () use ($actor, $id, $data) {
            $alert = EmergencyAlert::findOrFail($id);
            abort_unless($alert->status === 'pending' && $alert->revision === $data['revision'], 409, 'This alert was already sent, cancelled or changed. Refresh the page.');
            abort_if($alert->prepared_at->addMinutes(self::CONFIRM_MINUTES)->isPast(), 409, 'This alert was prepared too long ago. Prepare it again.');
            abort_unless(strtoupper(trim($data['confirmation'])) === self::confirmationWord($alert), 422, 'Type the confirmation word exactly to send this alert.');
            abort_if(EmergencyAlert::where('status', 'active')->exists(), 409, 'Another alert is active. Send its all-clear first.');
            $recipients = $this->recipients($alert->audience);
            $notice = $this->notice($alert, $this->title($alert, $alert->title), $this->body($alert, $alert->message), 'alert');
            $alert->forceFill(['status' => 'active', 'confirmed_by' => $actor->id, 'activated_at' => now()->utc(), 'notification_id' => $notice->id,
                'recipient_count' => count($recipients), 'revision' => $alert->revision + 1])->save();
            if ($alert->drill_id !== null) {
                $drill = EmergencyDrill::whereKey($alert->drill_id)->whereNull('alert_id')->where('status', 'planned')->first();
                $drill?->forceFill(['alert_id' => $alert->id, 'started_at' => now()->utc(), 'revision' => $drill->revision + 1])->save();
            }
            app(Notifier::class)->assign($notice, $recipients);
            EmergencyOperations::event($actor, 'alert.confirmed', $alert->id, $alert->id, ['kind' => $alert->kind, 'drill' => $alert->is_drill, 'recipients' => count($recipients), 'prepared_by' => (int) $alert->prepared_by]);
            Audit::record($alert->is_drill ? 'emergency.drill_alert_sent' : 'emergency.alert_sent', $alert, [], ['alert_id' => $alert->id, 'kind' => $alert->kind, 'audience' => $alert->audience,
                'prepared_by' => (int) $alert->prepared_by, 'confirmed_by' => (int) $actor->id, 'recipients' => count($recipients)]);

            return $this->summary($alert);
        });
    }

    /** The all-clear goes to the same people as the alert. */
    public function allClear(User $actor, string $id, array $input): array
    {
        $data = EmergencyOperations::input($input, self::rules('all_clear'));
        $authorize = function () use ($actor, $id) {
            app(EmergencyAccess::class)->alerter($actor);
            EmergencyAlert::findOrFail($id);
        };

        return app(EmergencyOperations::class)->once($actor, 'alert.all_clear', ['alert_id' => $id] + $data, $authorize, function () use ($actor, $id, $data) {
            $alert = EmergencyAlert::findOrFail($id);
            abort_unless($alert->status === 'active' && $alert->revision === $data['revision'], 409, 'This alert is not active. Refresh the page.');
            $message = trim((string) ($data['message'] ?? '')) ?: ($alert->is_drill ? __('The drill is over. Thank you, everyone.') : __('The emergency is over and it is safe. Thank you for your patience.'));
            $recipients = $this->recipients($alert->audience);
            $notice = $this->notice($alert, $this->title($alert, __('All clear: :title', ['title' => $alert->title])), $this->body($alert, $message), 'all_clear');
            $alert->forceFill(['status' => 'all_clear', 'all_clear_by' => $actor->id, 'all_clear_at' => now()->utc(), 'all_clear_message' => $message,
                'all_clear_notification_id' => $notice->id, 'revision' => $alert->revision + 1])->save();
            if ($alert->drill_id !== null) {
                $drill = EmergencyDrill::whereKey($alert->drill_id)->where('alert_id', $alert->id)->whereNull('ended_at')->first();
                $drill?->forceFill(['ended_at' => now()->utc(), 'revision' => $drill->revision + 1])->save();
            }
            app(Notifier::class)->assign($notice, $recipients);
            EmergencyOperations::event($actor, 'alert.all_clear', $alert->id, $alert->id, ['recipients' => count($recipients)]);
            Audit::record('emergency.all_clear_sent', $alert, [], ['alert_id' => $alert->id, 'kind' => $alert->kind, 'drill' => $alert->is_drill, 'all_clear_by' => (int) $actor->id]);

            return $this->summary($alert);
        });
    }

    /** A prepared alert that was never confirmed can be cancelled; nothing was sent. */
    public function cancel(User $actor, string $id, array $input): array
    {
        $data = EmergencyOperations::input($input, self::rules('cancel'));
        $authorize = function () use ($actor, $id) {
            app(EmergencyAccess::class)->alerter($actor);
            EmergencyAlert::findOrFail($id);
        };

        return app(EmergencyOperations::class)->once($actor, 'alert.cancel', ['alert_id' => $id] + $data, $authorize, function () use ($actor, $id, $data) {
            $alert = EmergencyAlert::findOrFail($id);
            abort_unless($alert->status === 'pending' && $alert->revision === $data['revision'], 409, 'Only an alert that has not been sent can be cancelled.');
            $alert->forceFill(['status' => 'cancelled', 'cancelled_by' => $actor->id, 'cancelled_at' => now()->utc(), 'revision' => $alert->revision + 1])->save();
            EmergencyOperations::event($actor, 'alert.cancelled', $alert->id, $alert->id);

            return $this->summary($alert);
        });
    }

    /**
     * Who is told: every active member of staff here and, unless the alert is
     * for staff only, every guardian. Never anyone who is a learner here.
     *
     * @return list<int>
     */
    public function recipients(string $audience): array
    {
        $school = app(CurrentSchool::class)->id();
        $learners = SchoolMembership::query()->active()->where('school_id', $school)->where('role', 'Student')->select('user_id');

        return SchoolMembership::query()->active()->where('school_id', $school)
            ->where('role', '!=', 'Student')->when($audience === 'staff', fn ($q) => $q->where('role', '!=', 'Parent'))
            ->whereNotIn('user_id', $learners)
            ->whereIn('user_id', User::query()->where('status', 1)->select('id'))
            ->orderBy('user_id')->pluck('user_id')->map(fn ($id) => (int) $id)->unique()->values()->all();
    }

    /** A drill always says so first, whatever the sender typed. */
    public function title(EmergencyAlert $alert, string $title): string
    {
        return $alert->is_drill ? __('DRILL: :title', ['title' => $title]) : $title;
    }

    public function body(EmergencyAlert $alert, string $message): string
    {
        return $alert->is_drill ? __('This is a drill. There is no emergency.').' '.$message : $message;
    }

    private function notice(EmergencyAlert $alert, string $title, string $body, string $stage): Notification
    {
        $notice = app(Notifier::class)->makeNotice(self::TYPE, $title, $body, '/notifications', null, $body);
        $notice->forceFill(['delivery_payload' => array_replace($notice->delivery_payload ?? [], [
            'emergency_alert_id' => $alert->id, 'emergency_kind' => $alert->kind, 'emergency_stage' => $stage, 'drill' => $alert->is_drill ? '1' : '0',
        ])])->save();

        return $notice;
    }
}
