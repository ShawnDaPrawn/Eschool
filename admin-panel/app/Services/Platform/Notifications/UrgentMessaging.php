<?php

declare(strict_types=1);

namespace App\Services\Platform\Notifications;

use App\Jobs\Platform\SendText;
use App\Models\Notification;
use App\Models\Parents;
use App\Models\Platform\MessagingChoice;
use App\Models\Platform\MessagingNumber;
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
 * school that switched the channel and the type on for guardians or staff,
 * only to a number its owner has verified (TextNumbers): for guardians the
 * one the school office recorded on their parent record at this school, for
 * staff the one on their own account. Each person chooses SMS, WhatsApp,
 * both or neither per type. Off by default.
 * engineering/specs/platform/sms-whatsapp.md.
 */
class UrgentMessaging
{
    public const CHANNELS = [DeliveryLog::SMS, DeliveryLog::WHATSAPP];

    /** The delivery reason of a text not sent because its number is not verified. */
    public const UNVERIFIED = 'Not sent: the mobile number has not been verified by its owner.';

    public const GUARDIAN = 'guardian';

    public const STAFF = 'staff';

    public const AUDIENCES = [self::GUARDIAN, self::STAFF];

    public function __construct(private readonly NotificationRules $rules) {}

    /**
     * The types that can ever be texted, or only those this audience can be
     * texted.
     *
     * @return list<string>
     */
    public function eligibleTypes(?string $audience = null): array
    {
        return array_values(array_filter(array_keys((array) config('urgent_messaging.types', [])), fn ($type) => isset(config('notification_types', [])[$type])
            && ($audience === null || in_array($audience, $this->audiences($type), true))));
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

    /** @return list<string> who this type may be texted to */
    public function audiences(string $type): array
    {
        return array_values(array_intersect(self::AUDIENCES, (array) ($this->definition($type)['audiences'] ?? [self::GUARDIAN])));
    }

    /** @return list<string> the portals of an audience */
    public function portals(string $audience): array
    {
        return array_values((array) (((array) config('urgent_messaging.audiences', []))[$audience] ?? ($audience === self::GUARDIAN ? ['parent'] : [])));
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
     * Whether the current school sends this type by this channel to this
     * audience (guardians unless said otherwise).
     */
    public function enabledFor(string $type, string $channel, string $audience = self::GUARDIAN): bool
    {
        if (! in_array($channel, self::CHANNELS, true) || ! in_array($type, $this->eligibleTypes($audience), true)
            || ($channel === DeliveryLog::WHATSAPP && ! $this->whatsappAvailable($type))) {
            return false;
        }
        $setting = $this->setting();
        if (! $setting) {
            return false;
        }
        $types = match ([$audience, $channel]) {
            [self::GUARDIAN, DeliveryLog::SMS] => $setting->sms_types,
            [self::GUARDIAN, DeliveryLog::WHATSAPP] => $setting->whatsapp_types,
            [self::STAFF, DeliveryLog::SMS] => $setting->staff_sms_types,
            default => $setting->staff_whatsapp_types,
        };

        return $channel === DeliveryLog::SMS
            ? $setting->sms_enabled && in_array($type, $types ?? [], true)
            : $setting->whatsapp_enabled && $setting->whatsapp_consent_confirmed_at !== null && in_array($type, $types ?? [], true);
    }

    /**
     * Which audience this person is texted this type as by this channel at
     * the current school: guardian first, then staff. Null when neither is
     * switched on for them.
     */
    public function audienceFor(User $user, string $type, string $channel): ?string
    {
        $portals = $this->rules->portals($user);
        foreach ($this->audiences($type) as $audience) {
            if (array_intersect($portals, $this->portals($audience)) !== [] && $this->enabledFor($type, $channel, $audience)) {
                return $audience;
            }
        }

        return null;
    }

    /**
     * Whether this person may be texted this type by this channel now: the
     * school switched it on for them, the type is offered to them, and their
     * own choice allows it.
     */
    public function allows(User $user, string $type, string $channel): bool
    {
        $audience = $this->audienceFor($user, $type, $channel);

        return $audience !== null && $this->rules->offered($user, $type) && $this->chosen($user, $type, $channel, $audience);
    }

    /**
     * The person's own choice for this type and channel. Without a choice a
     * guardian gets what the school switched on (the school confirmed
     * guardians' WhatsApp consent); staff get SMS, and WhatsApp only once they
     * choose it themselves.
     */
    public function chosen(User $user, string $type, string $channel, string $audience): bool
    {
        $choice = MessagingChoice::query()->where('user_id', $user->id)->where('type', $type)->first();
        if ($choice !== null) {
            return $channel === DeliveryLog::SMS ? $choice->sms : $choice->whatsapp;
        }

        return $channel === DeliveryLog::SMS || $audience === self::GUARDIAN;
    }

    /** Whether the school texts this person anything at all (before their own choice). */
    public function anyChannelFor(User $user): bool
    {
        foreach ($this->eligibleTypes() as $type) {
            foreach (self::CHANNELS as $channel) {
                if ($this->audienceFor($user, $type, $channel) !== null && $this->rules->offered($user, $type)) {
                    return true;
                }
            }
        }

        return false;
    }

    /**
     * The number this person is texted on at the current school, in E.164:
     * the one on their parent record here (which the school office keeps),
     * or for staff with no parent record here, the one on their own account.
     * Never another school's parent record. Null without one.
     *
     * @return array{number: string, source: string}|null
     */
    public function destination(User $user): ?array
    {
        $schoolId = app(CurrentSchool::class)->idOrNull();
        if ($schoolId === null) {
            return null;
        }
        $records = Parents::query()->where('school_id', $schoolId)->where('user_id', $user->id)->orderBy('id')->pluck('mobile');
        foreach ($records as $raw) {
            $number = is_string($raw) ? self::normalise($raw) : null;
            if ($number !== null) {
                return ['number' => $number, 'source' => MessagingNumber::PARENT];
            }
        }
        if ($records->isEmpty() && array_intersect($this->rules->portals($user), $this->portals(self::STAFF)) !== []) {
            $number = is_string($user->mobile) ? self::normalise($user->mobile) : null;
            if ($number !== null) {
                return ['number' => $number, 'source' => MessagingNumber::ACCOUNT];
            }
        }

        return null;
    }

    /** The destination's number only. */
    public function number(User $user): ?string
    {
        return $this->destination($user)['number'] ?? null;
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
            $verified = app(TextNumbers::class)->verified($user, $number);
            $content = $verified ? $this->content($channel, $type, $notice, $title, $body) : [];
            DB::transaction(function () use ($user, $notice, $type, $channel, $number, $content, $verified, &$queued) {
                $claim = DB::table('notification_dispatches')->insertOrIgnore([
                    'school_id' => app(CurrentSchool::class)->id(), 'notification_id' => $notice->id,
                    'user_id' => $user->id, 'channel' => $channel, 'created_at' => now(), 'updated_at' => now(),
                ]);
                if ($claim === 0) {
                    return;
                }
                if (! $verified) {
                    // Recorded once, with the reason, and never sent.
                    DeliveryLog::skipped($channel, $channel.'.'.$type, (int) $user->id, $number, self::UNVERIFIED);

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
     * sms_types and whatsapp_types are for guardians; staff_sms_types and
     * staff_whatsapp_types for staff.
     *
     * @param  array<string, mixed>  $input
     */
    public function change(User $actor, array $input): SchoolMessagingSetting
    {
        abort_unless(self::mayManage($actor), 403);
        $guardian = $this->eligibleTypes(self::GUARDIAN);
        $staff = $this->eligibleTypes(self::STAFF);
        $withTemplate = fn (array $types) => array_values(array_filter($types, fn ($type) => $this->whatsappAvailable($type)));
        $noTemplate = __('This notice cannot go by WhatsApp until NavigateEd has an approved template for it.');
        $data = validator($input, [
            'revision' => ['required', 'integer', 'min:0'],
            'sms_enabled' => ['required', 'boolean'],
            'whatsapp_enabled' => ['required', 'boolean'],
            'sms_types' => ['nullable', 'array'],
            'sms_types.*' => ['string', 'distinct', 'in:'.implode(',', $guardian)],
            'whatsapp_types' => ['nullable', 'array'],
            'whatsapp_types.*' => ['string', 'distinct', 'in:'.implode(',', $withTemplate($guardian))],
            'staff_sms_types' => ['nullable', 'array'],
            'staff_sms_types.*' => ['string', 'distinct', 'in:'.implode(',', $staff)],
            'staff_whatsapp_types' => ['nullable', 'array'],
            'staff_whatsapp_types.*' => ['string', 'distinct', 'in:'.implode(',', $withTemplate($staff))],
            'whatsapp_consent' => ['nullable', 'boolean'],
        ], [
            'whatsapp_types.*.in' => $noTemplate,
            'staff_whatsapp_types.*.in' => $noTemplate,
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
                'staff_sms_types' => array_values(array_unique($data['staff_sms_types'] ?? [])),
                'staff_whatsapp_types' => array_values(array_unique($data['staff_whatsapp_types'] ?? [])),
                'whatsapp_consent_confirmed_at' => $whatsapp ? ($confirmed ?? now()) : $confirmed,
                'changed_by' => $actor->id,
                'revision' => (int) $setting->revision + 1,
            ])->save();
            Audit::record('messaging.settings_changed', $setting, $before, $this->summary($setting));

            return $setting;
        });
    }

    /**
     * The settings page: each eligible type, who it can go to, whether each
     * channel can and does carry it, and how many people chose for
     * themselves (only that a choice exists, never what it is).
     *
     * @return array{setting: array<string, mixed>, types: list<array<string, mixed>>, configured: array<string, bool>, people: array<string, int>}
     */
    public function overview(): array
    {
        $setting = $this->setting();
        $own = $this->ownChoices();
        $types = [];
        foreach ($this->eligibleTypes() as $type) {
            $audiences = $this->audiences($type);
            $types[] = [
                'type' => $type,
                'label' => (string) (config('notification_types', [])[$type]['label'] ?? $type),
                'content' => (string) ($this->definition($type)['content'] ?? 'notice'),
                'guardian' => in_array(self::GUARDIAN, $audiences, true),
                'staff' => in_array(self::STAFF, $audiences, true),
                'sms' => in_array($type, $setting?->sms_types ?? [], true),
                'whatsapp' => in_array($type, $setting?->whatsapp_types ?? [], true),
                'staff_sms' => in_array($type, $setting?->staff_sms_types ?? [], true),
                'staff_whatsapp' => in_array($type, $setting?->staff_whatsapp_types ?? [], true),
                'whatsapp_available' => $this->whatsappAvailable($type),
                'own_choices' => $own[$type] ?? 0,
            ];
        }
        $numbers = MessagingNumber::query()->whereNotNull('verified_at')->whereNotNull('number_hash')->count();

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
            'people' => ['verified' => $numbers],
        ];
    }

    /**
     * How many people at the current school made their own choice, per type.
     * The school sees only this count: who chose what stays with each person.
     *
     * @return array<string, int>
     */
    public function ownChoices(): array
    {
        return MessagingChoice::query()->groupBy('type')->selectRaw('type, COUNT(*) as people')->pluck('people', 'type')
            ->map(fn ($count) => (int) $count)->all();
    }

    /**
     * The person's own SMS and WhatsApp choice for each type the school texts
     * them, with only the channels the school switched on for them.
     *
     * @return list<array<string, mixed>>
     */
    public function choices(User $user): array
    {
        $rows = MessagingChoice::query()->where('user_id', $user->id)->get()->keyBy('type');
        $answer = [];
        foreach ($this->eligibleTypes() as $type) {
            if (! $this->rules->offered($user, $type)) {
                continue;
            }
            $channels = [];
            foreach (self::CHANNELS as $channel) {
                $audience = $this->audienceFor($user, $type, $channel);
                if ($audience !== null) {
                    $channels[$channel] = $this->chosen($user, $type, $channel, $audience);
                }
            }
            if ($channels === []) {
                continue;
            }
            $answer[] = [
                'type' => $type,
                'label' => __((string) (config('notification_types', [])[$type]['label'] ?? $type)),
                'offered' => array_keys($channels),
                'sms' => $channels[DeliveryLog::SMS] ?? false,
                'whatsapp' => $channels[DeliveryLog::WHATSAPP] ?? false,
                'own_choice' => $rows->has($type),
            ];
        }

        return $answer;
    }

    /**
     * The person sets SMS and WhatsApp for one type: either, both or neither.
     * A channel the school has not switched on for them cannot be chosen.
     * Only the person themselves: there is no id to name anyone else.
     *
     * @return list<array<string, mixed>>
     */
    public function choose(User $user, string $type, bool $sms, bool $whatsapp): array
    {
        $offered = collect($this->choices($user))->firstWhere('type', $type);
        if ($offered === null) {
            throw ValidationException::withMessages(['type' => __('This school does not send this notice to you by SMS or WhatsApp.')]);
        }
        foreach ([DeliveryLog::SMS => $sms, DeliveryLog::WHATSAPP => $whatsapp] as $channel => $wanted) {
            if ($wanted && ! in_array($channel, $offered['offered'], true)) {
                throw ValidationException::withMessages([$channel => __('This school does not send this notice by :channel.', ['channel' => $channel === DeliveryLog::SMS ? 'SMS' : 'WhatsApp'])]);
            }
        }
        DB::transaction(function () use ($user, $type, $sms, $whatsapp) {
            $row = MessagingChoice::query()->where('user_id', $user->id)->where('type', $type)->lockForUpdate()->first();
            $before = $row === null ? [] : ['type' => $type, 'sms' => $row->sms, 'whatsapp' => $row->whatsapp];
            $row ??= new MessagingChoice(['user_id' => $user->id, 'type' => $type]);
            $row->forceFill(['sms' => $sms, 'whatsapp' => $whatsapp])->save();
            Audit::record('messaging.choice_changed', $row, $before, ['type' => $type, 'sms' => $sms, 'whatsapp' => $whatsapp]);
        });

        return $this->choices($user);
    }

    /** @return array<string, mixed> */
    private function summary(SchoolMessagingSetting $setting): array
    {
        return [
            'sms_enabled' => $setting->sms_enabled, 'whatsapp_enabled' => $setting->whatsapp_enabled,
            'sms_types' => $setting->sms_types ?? [], 'whatsapp_types' => $setting->whatsapp_types ?? [],
            'staff_sms_types' => $setting->staff_sms_types ?? [], 'staff_whatsapp_types' => $setting->staff_whatsapp_types ?? [],
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
