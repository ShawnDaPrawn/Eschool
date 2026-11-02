<?php

declare(strict_types=1);

namespace App\Services\Platform\Notifications;

use App\Jobs\Platform\SendText;
use App\Models\Notification;
use App\Models\Parents;
use App\Models\Platform\SchoolMessagingSetting;
use App\Models\User;
use App\Services\Platform\Audit\Audit;
use App\Services\Platform\Tenancy\CurrentSchool;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;
use Throwable;

/**
 * SMS and WhatsApp for urgent and safety notices (Action Plan 2.7), beside
 * mail and push. Only the types in config/urgent_messaging.php, only at a
 * school that switched the channel and the type on, only to guardians with a
 * number the school office recorded on their parent record at this school.
 * Off by default. engineering/specs/platform/sms-whatsapp.md.
 */
class UrgentMessaging
{
    public const CHANNELS = [DeliveryLog::SMS, DeliveryLog::WHATSAPP];

    public function __construct(private readonly NotificationRules $rules) {}

    /** @return list<string> */
    public function eligibleTypes(): array
    {
        return array_values(array_filter(array_keys((array) config('urgent_messaging.types', [])), fn ($type) => isset(config('notification_types', [])[$type])));
    }

    /**
     * The type's entry in config/urgent_messaging.php (types have dots in
     * their names, so never a dotted config() path).
     *
     * @return array<string, mixed>
     */
    public function definition(string $type): array
    {
        return (array) (((array) config('urgent_messaging.types', []))[$type] ?? []);
    }

    /** Whether WhatsApp can carry this type: it needs an approved template. */
    public function whatsappAvailable(string $type): bool
    {
        return filled($this->definition($type)['whatsapp_notice_template'] ?? null);
    }

    /** Office staff of this school with the settings permission. */
    public static function mayManage(?User $user): bool
    {
        return $user !== null && ($user->hasRole('Super Admin') || (! $user->hasAnyRole(['Parent', 'Student', 'Teacher']) && $user->can('setting-create')));
    }

    /** The current school's setting, or null when it never opted in. */
    public function setting(): ?SchoolMessagingSetting
    {
        return SchoolMessagingSetting::query()->where('school_id', app(CurrentSchool::class)->id())->first();
    }

    /**
     * Whether the current school sends this type by this channel.
     */
    public function enabledFor(string $type, string $channel): bool
    {
        if (! in_array($channel, self::CHANNELS, true) || ! in_array($type, $this->eligibleTypes(), true)
            || ($channel === DeliveryLog::WHATSAPP && ! $this->whatsappAvailable($type))) {
            return false;
        }
        $setting = $this->setting();
        if (! $setting) {
            return false;
        }

        return $channel === DeliveryLog::SMS
            ? $setting->sms_enabled && in_array($type, $setting->sms_types ?? [], true)
            : $setting->whatsapp_enabled && $setting->whatsapp_consent_confirmed_at !== null && in_array($type, $setting->whatsapp_types ?? [], true);
    }

    /** Whether this person may be texted this type by this channel now. */
    public function allows(User $user, string $type, string $channel): bool
    {
        return $this->enabledFor($type, $channel) && $this->rules->offered($user, $type)
            && array_intersect($this->rules->portals($user), (array) config('urgent_messaging.portals', ['parent'])) !== [];
    }

    /**
     * The guardian's mobile number in E.164, from the parent record the
     * school office keeps at the current school (never the shared users row,
     * which any school the person belongs to could change). Null without one.
     */
    public function number(User $user): ?string
    {
        $schoolId = app(CurrentSchool::class)->idOrNull();
        if ($schoolId === null) {
            return null;
        }
        $numbers = Parents::query()->where('school_id', $schoolId)->where('user_id', $user->id)->orderBy('id')->pluck('mobile');
        foreach ($numbers as $raw) {
            $number = is_string($raw) ? self::normalise($raw) : null;
            if ($number !== null) {
                return $number;
            }
        }

        return null;
    }

    /** A recorded number in E.164 (+27821234567), or null when it is not one. */
    public static function normalise(string $raw): ?string
    {
        $digits = preg_replace('/[\s\-().]/', '', trim($raw)) ?? '';
        $country = preg_replace('/\D/', '', (string) config('urgent_messaging.default_country_code', '27')) ?: '27';
        if (str_starts_with($digits, '00')) {
            $digits = '+'.substr($digits, 2);
        } elseif (preg_match('/^0[1-9][0-9]{8}$/', $digits)) {
            $digits = '+'.$country.substr($digits, 1);
        } elseif (! str_starts_with($digits, '+') && str_starts_with($digits, $country) && strlen($digits) === strlen($country) + 9) {
            $digits = '+'.$digits;
        }

        return preg_match('/^\+[1-9][0-9]{7,14}$/', $digits) ? $digits : null;
    }

    /**
     * Queue the texts this notice should send to this person, each channel
     * claimed once per notice and person so a retried fanout never texts
     * again. Called by Notifier::send after mail and push. Returns the
     * channels queued.
     *
     * @param  list<string>|null  $channels
     * @return list<string>
     */
    public function queue(User $user, Notification $notice, string $type, string $title, string $body, ?array $channels = null): array
    {
        $queued = [];
        foreach (self::CHANNELS as $channel) {
            // Until NavigateEd's Twilio account is set up, nothing is queued for that channel.
            if (($channels !== null && ! in_array($channel, $channels, true)) || ! $this->allows($user, $type, $channel)
                || ! app(TwilioClient::class)->configured($channel)) {
                continue;
            }
            $number = $this->number($user);
            if ($number === null) {
                continue;
            }
            $content = $this->content($channel, $type, $notice, $title, $body);
            DB::transaction(function () use ($user, $notice, $type, $channel, $number, $content, &$queued) {
                $claim = DB::table('notification_dispatches')->insertOrIgnore([
                    'school_id' => app(CurrentSchool::class)->id(), 'notification_id' => $notice->id,
                    'user_id' => $user->id, 'channel' => $channel, 'created_at' => now(), 'updated_at' => now(),
                ]);
                if ($claim === 0) {
                    return;
                }
                $key = DeliveryLog::queued($channel, $channel.'.'.$type, (int) $user->id, $number);
                $this->dispatch(new SendText($channel, $type, $number, $content, $key, $notice->delivery_payload ?? []));
                $queued[] = $channel;
            });
        }

        return $queued;
    }

    /**
     * What the text says. Never the body of a notice that may be about one
     * child: only a whole-school urgent notice carries the school's words.
     *
     * @return array{body?: string, content_sid?: string, variables?: array<string, string>}
     */
    public function content(string $channel, string $type, Notification $notice, string $title, string $body): array
    {
        $school = Str::limit(trim((string) app(CurrentSchool::class)->school()?->name) ?: 'your school', 80, '');
        $definition = $this->definition($type);
        $withText = ($definition['content'] ?? 'notice') === 'broadcast_text' && (int) $notice->send_to === 1;
        $text = Str::limit(trim(preg_replace('/\s+/', ' ', trim($title).'. '.trim($body)) ?? ''), 300);

        if ($channel === DeliveryLog::WHATSAPP) {
            if ($withText && filled($definition['whatsapp_text_template'] ?? null)) {
                return ['content_sid' => (string) $definition['whatsapp_text_template'], 'variables' => ['1' => $school, '2' => $text]];
            }

            return ['content_sid' => (string) ($definition['whatsapp_notice_template'] ?? ''), 'variables' => ['1' => $school]];
        }

        return ['body' => $withText
            ? __('Urgent from :school: :text Open the NavigateEd app for more.', ['school' => $school, 'text' => $text])
            : __('You have an urgent message from :school. Open the NavigateEd app.', ['school' => $school])];
    }

    /**
     * Save the current school's choice. Validated again here, whoever calls.
     *
     * @param  array<string, mixed>  $input
     */
    public function change(User $actor, array $input): SchoolMessagingSetting
    {
        abort_unless(self::mayManage($actor), 403);
        $eligible = $this->eligibleTypes();
        $whatsappTypes = array_values(array_filter($eligible, fn ($type) => $this->whatsappAvailable($type)));
        $data = validator($input, [
            'revision' => ['required', 'integer', 'min:0'],
            'sms_enabled' => ['required', 'boolean'],
            'whatsapp_enabled' => ['required', 'boolean'],
            'sms_types' => ['nullable', 'array'],
            'sms_types.*' => ['string', 'distinct', 'in:'.implode(',', $eligible)],
            'whatsapp_types' => ['nullable', 'array'],
            'whatsapp_types.*' => ['string', 'distinct', 'in:'.implode(',', $whatsappTypes)],
            'whatsapp_consent' => ['nullable', 'boolean'],
        ], [
            'whatsapp_types.*.in' => __('This notice cannot go by WhatsApp until NavigateEd has an approved template for it.'),
        ])->validate();

        return DB::transaction(function () use ($actor, $data) {
            $setting = SchoolMessagingSetting::query()->where('school_id', app(CurrentSchool::class)->id())->lockForUpdate()->first();
            if ((int) $data['revision'] !== (int) ($setting?->revision ?? 0)) {
                throw ValidationException::withMessages(['revision' => __('Someone else changed these settings. Reload the page and try again.')]);
            }
            $whatsapp = (bool) $data['whatsapp_enabled'];
            $confirmed = $setting?->whatsapp_consent_confirmed_at;
            if ($whatsapp && $confirmed === null && ! ($data['whatsapp_consent'] ?? false)) {
                throw ValidationException::withMessages(['whatsapp_consent' => __('Confirm that guardians have agreed to receive WhatsApp messages from the school.')]);
            }
            $before = $setting ? $this->summary($setting) : [];
            $setting ??= new SchoolMessagingSetting(['revision' => 0]);
            $setting->forceFill([
                'sms_enabled' => (bool) $data['sms_enabled'],
                'whatsapp_enabled' => $whatsapp,
                'sms_types' => array_values(array_unique($data['sms_types'] ?? [])),
                'whatsapp_types' => array_values(array_unique($data['whatsapp_types'] ?? [])),
                'whatsapp_consent_confirmed_at' => $whatsapp ? ($confirmed ?? now()) : $confirmed,
                'changed_by' => $actor->id,
                'revision' => (int) $setting->revision + 1,
            ])->save();
            Audit::record('messaging.settings_changed', $setting, $before, $this->summary($setting));

            return $setting;
        });
    }

    /**
     * The settings page: each eligible type and whether each channel can and
     * does carry it.
     *
     * @return array{setting: array<string, mixed>, types: list<array<string, mixed>>, configured: array<string, bool>}
     */
    public function overview(): array
    {
        $setting = $this->setting();
        $types = [];
        foreach ($this->eligibleTypes() as $type) {
            $types[] = [
                'type' => $type,
                'label' => (string) (config('notification_types', [])[$type]['label'] ?? $type),
                'content' => (string) ($this->definition($type)['content'] ?? 'notice'),
                'sms' => in_array($type, $setting?->sms_types ?? [], true),
                'whatsapp' => in_array($type, $setting?->whatsapp_types ?? [], true),
                'whatsapp_available' => $this->whatsappAvailable($type),
            ];
        }

        return [
            'setting' => [
                'sms_enabled' => (bool) $setting?->sms_enabled,
                'whatsapp_enabled' => (bool) $setting?->whatsapp_enabled,
                'whatsapp_consent_confirmed' => $setting?->whatsapp_consent_confirmed_at !== null,
                'revision' => (int) ($setting?->revision ?? 0),
            ],
            'types' => $types,
            'configured' => [
                DeliveryLog::SMS => app(TwilioClient::class)->configured(DeliveryLog::SMS),
                DeliveryLog::WHATSAPP => app(TwilioClient::class)->configured(DeliveryLog::WHATSAPP),
            ],
        ];
    }

    /** @return array<string, mixed> */
    private function summary(SchoolMessagingSetting $setting): array
    {
        return [
            'sms_enabled' => $setting->sms_enabled, 'whatsapp_enabled' => $setting->whatsapp_enabled,
            'sms_types' => $setting->sms_types ?? [], 'whatsapp_types' => $setting->whatsapp_types ?? [],
            'whatsapp_consent_confirmed' => $setting->whatsapp_consent_confirmed_at !== null,
        ];
    }

    private function dispatch(SendText $job): void
    {
        try {
            if (config('queue.connections.'.config('queue.default').'.driver') === 'database') {
                // The queue row and the recipient claim commit together.
                $job->beforeCommit();
            }
            dispatch($job);
        } catch (Throwable $e) {
            if (config('queue.default') !== 'sync') {
                throw $e;
            }
            // Only with the sync queue, where the job runs inside dispatch(). A
            // text never fails the action that triggered it.
            Log::error('Text notification failed', ['delivery_key' => $job->deliveryKey, 'exception' => get_class($e)]);
        }
    }
}
