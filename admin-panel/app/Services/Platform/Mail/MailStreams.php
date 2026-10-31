<?php

declare(strict_types=1);

namespace App\Services\Platform\Mail;

use App\Mail\Platform\NotificationMail;
use App\Models\Platform\MailStream;
use App\Models\Platform\NotificationDelivery;
use App\Models\Platform\School;
use App\Models\User;
use App\Services\Platform\Audit\Audit;
use App\Services\Platform\Saas\PlatformStaffAccess;
use Aws\Exception\AwsException;
use Illuminate\Mail\Mailable;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

/**
 * SES streams (Action Plan 2.3). Every school sends through two Amazon SES
 * configuration sets, one per stream: "account" (sign-in, passwords,
 * invitations, welcome mails and safety notices) and "bulk" (every other
 * notice), so a school's newsletters can never spoil the reputation its
 * password resets and safeguarding alerts depend on, and one school's
 * bounces show up against that school. Mail without a school uses the
 * platform's own pair.
 *
 * SendMail asks configurationSetFor() with the delivery row's own school,
 * never the ambient one. ensure() creates a school's pair in SES (idempotent),
 * the hourly poll keeps every school's pair set up and checked, and the
 * webhook records the tenant sending status events SES publishes. Off by
 * default (mail.streams.enabled). engineering/specs/platform/ses-streams.md.
 */
class MailStreams
{
    public const ACCOUNT = 'account';

    public const BULK = 'bulk';

    public const STREAMS = [self::ACCOUNT, self::BULK];

    /** Statuses a sent mail can end in; failed and suppressed mail never reached SES or was refused there. */
    private const SENT_STATUSES = [NotificationDelivery::SENT, NotificationDelivery::DELIVERED, NotificationDelivery::BOUNCED, NotificationDelivery::COMPLAINED];

    public function __construct(private readonly SesConfigurationSets $ses) {}

    public static function enabled(): bool
    {
        return (bool) config('mail.streams.enabled');
    }

    /** The stream a mail goes out on: notices of the account types, and every mail that is not a notice, are account mail. */
    public static function streamFor(Mailable $mail): string
    {
        return $mail instanceof NotificationMail ? self::streamForType($mail->notificationType) : self::ACCOUNT;
    }

    public static function streamForType(string $notificationType): string
    {
        foreach ((array) config('mail.streams.account_types', []) as $pattern) {
            if (Str::is((string) $pattern, $notificationType)) {
                return self::ACCOUNT;
            }
        }

        return self::BULK;
    }

    /** The set's name: <prefix>-s<school id>-<stream>, or <prefix>-platform-<stream> for no school. */
    public static function name(?int $schoolId, string $stream): string
    {
        $prefix = trim((string) preg_replace('/[^a-z0-9-]+/', '', strtolower((string) config('mail.streams.prefix', 'ne'))), '-');
        $prefix = substr($prefix === '' ? 'ne' : $prefix, 0, 20);

        return $schoolId === null ? $prefix.'-platform-'.$stream : $prefix.'-s'.$schoolId.'-'.$stream;
    }

    /**
     * The configuration set for a mail of this school and stream: the
     * school's own once it is ready, otherwise the platform's, otherwise none
     * (SES then sends without one, as before streams). Read by the explicit
     * school the delivery row names.
     */
    public function configurationSetFor(?int $schoolId, string $stream): ?string
    {
        if (! self::enabled() || ! in_array($stream, self::STREAMS, true)) {
            return null;
        }
        foreach (array_unique([$schoolId, null], SORT_REGULAR) as $owner) {
            $name = self::name($owner, $stream);
            $ready = MailStream::query()->where('configuration_set', $name)->where('status', MailStream::READY)
                ->when($owner === null, fn ($q) => $q->whereNull('school_id'), fn ($q) => $q->where('school_id', $owner))->exists();
            if ($ready) {
                return $name;
            }
        }

        return null;
    }

    /**
     * Sets up the school's (or, for null, the platform's) two configuration
     * sets in SES: created when missing, with reputation metrics, VDM options
     * and the feedback event destination; an existing set is brought up to
     * date and never unpaused. Safe to run again.
     *
     * @return list<MailStream>
     */
    public function ensure(?int $schoolId): array
    {
        if (! self::enabled()) {
            return [];
        }
        $topic = self::eventTopicArn();
        $rows = [];
        foreach (self::STREAMS as $stream) {
            $name = self::name($schoolId, $stream);
            $row = MailStream::query()->firstOrCreate(['configuration_set' => $name], ['school_id' => $schoolId, 'stream' => $stream, 'status' => MailStream::PENDING]);
            if ($row->school_id !== $schoolId || $row->stream !== $stream) {
                // A row of this name for another owner is never adopted.
                Log::error('mail_stream_name_conflict', ['configuration_set' => $name, 'school_id' => $schoolId]);

                continue;
            }
            $rows[] = $topic === null ? $this->failed($row, 'no_event_topic') : $this->setUp($row, $topic);
        }

        return $rows;
    }

    /**
     * HQ's "Set up or check now" for one school (or the platform's pair when
     * $schoolId is null).
     *
     * @return list<MailStream>
     */
    public function ensureForHq(?int $schoolId, User $actor): array
    {
        abort_unless(app(PlatformStaffAccess::class)->can($actor, 'mail.manage'), 403);
        if ($schoolId !== null) {
            abort_if(School::query()->whereKey($schoolId)->doesntExist(), 404);
        }
        if (! self::enabled()) {
            throw ValidationException::withMessages(['streams' => 'SES streams are not switched on (MAIL_SES_STREAMS).']);
        }
        $rows = $this->ensure($schoolId);
        Audit::record('platform_mail_streams_checked', null, [], [
            'streams' => array_map(fn (MailStream $row) => ['configuration_set' => $row->configuration_set, 'status' => $row->status, 'last_error' => $row->last_error], $rows),
        ], $schoolId);

        return $rows;
    }

    /** Asks SES about a ready set again: whether it may send, and whether it still exists. */
    public function refresh(MailStream $row): MailStream
    {
        if (! self::enabled()) {
            return $row;
        }
        try {
            $status = $this->ses->status($row->configuration_set);
        } catch (AwsException $e) {
            Log::warning('mail_stream_check_failed', ['configuration_set' => $row->configuration_set, 'exception' => $e::class]);
            $row->forceFill(['last_error' => 'ses_unreachable', 'checked_at' => now()])->save();

            return $row;
        }
        if ($status === null) {
            // Mail naming a set SES no longer has would be refused: stop using it.
            $this->audit($row, 'mail_stream.missing', ['status' => $row->status], ['status' => MailStream::FAILED]);

            return $this->failed($row, 'missing_in_ses');
        }
        $this->record($row, $status);

        return $row;
    }

    /**
     * The scheduled run (mail:ensure-streams): sets up the platform pair and
     * every school's pair that is not ready, then checks each ready set that
     * has not been checked for a day.
     */
    public function poll(?int $onlySchool = null): int
    {
        if (! self::enabled() || ($onlySchool !== null && School::query()->whereKey($onlySchool)->doesntExist())) {
            return 0;
        }
        $owners = $onlySchool !== null ? [$onlySchool]
            : [null, ...School::query()->where('status', '!=', School::ARCHIVED)->orderBy('id')->pluck('id')->map(fn ($id) => (int) $id)->all()];
        $touched = 0;
        foreach ($owners as $owner) {
            $ready = MailStream::query()->whereIn('configuration_set', array_map(fn (string $stream) => self::name($owner, $stream), self::STREAMS))
                ->where('status', MailStream::READY)->count();
            if ($ready < count(self::STREAMS) || $onlySchool !== null) {
                $touched += count($this->ensure($owner));
                if ($touched >= 200) {
                    break;
                }
            }
        }
        $due = MailStream::query()->where('status', MailStream::READY)
            ->where(fn ($q) => $q->whereNull('checked_at')->orWhere('checked_at', '<=', now()->subDay()))
            ->orderBy('id')->limit(500)->get();
        foreach ($due as $row) {
            $this->refresh($row);
        }

        return $touched + $due->count();
    }

    /**
     * Records a tenant sending status event SES published (through
     * EventBridge and the feedback SNS topic) on the school's streams. Only a
     * tenant named like the school's sets (<prefix>-s<school id>, or
     * <prefix>-platform) is recognised; others are ignored.
     *
     * @return int the streams updated
     */
    public function tenantStatus(string $tenantName, string $status, string $origin, string $cause, Carbon $at): int
    {
        $owner = self::tenantOwner($tenantName);
        if ($owner === false || ! in_array($status, ['ENABLED', 'DISABLED', 'REINSTATED'], true)) {
            return 0;
        }
        $rows = MailStream::query()->whereIn('configuration_set', array_map(fn (string $stream) => self::name($owner, $stream), self::STREAMS))
            ->when($owner === null, fn ($q) => $q->whereNull('school_id'), fn ($q) => $q->where('school_id', $owner))->get();
        $updated = 0;
        foreach ($rows as $row) {
            // Events can arrive out of order: an older one never replaces a newer one.
            if ($row->tenant_status_at !== null && $row->tenant_status_at->greaterThan($at)) {
                continue;
            }
            $before = ['tenant_status' => $row->tenant_status, 'tenant_status_origin' => $row->tenant_status_origin];
            $row->forceFill([
                'tenant_status' => $status,
                'tenant_status_origin' => Str::limit($origin, 20, ''),
                'tenant_status_cause' => Str::limit($cause, 191, ''),
                'tenant_status_at' => $at,
            ])->save();
            $this->audit($row, 'mail_stream.tenant_status', $before, ['tenant_status' => $status, 'tenant_status_origin' => $row->tenant_status_origin]);
            $updated++;
        }

        return $updated;
    }

    /**
     * The /saas mail streams page: for the platform and each school, each
     * stream's configuration set and status, and its mail over the last
     * report_days days with bounce and complaint rates, from
     * notification_deliveries. Counts only, never a person.
     *
     * @return array{enabled: bool, days: int, targets: list<array{school_id: int|null, name: string, school_status: string|null, streams: list<array<string, mixed>>}>}
     */
    public function report(): array
    {
        $days = max(1, (int) config('mail.streams.report_days', 30));
        $counts = [];
        DB::table('notification_deliveries')
            ->where('channel', 'mail')->whereNotNull('mail_stream')
            ->where('created_at', '>=', now()->subDays($days))
            ->whereIn('status', self::SENT_STATUSES)
            ->groupBy('school_id', 'mail_stream', 'status')
            ->selectRaw('school_id, mail_stream, status, COUNT(*) AS n')
            ->get()
            ->each(function (object $row) use (&$counts) {
                $counts[($row->school_id === null ? 'platform' : (int) $row->school_id).':'.$row->mail_stream][(string) $row->status] = (int) $row->n;
            });
        $streams = MailStream::query()->get()->keyBy('configuration_set');

        $targets = [['school_id' => null, 'name' => 'Platform (mail for no school)', 'school_status' => null]];
        foreach (School::query()->orderBy('name')->limit(500)->get(['id', 'name', 'status']) as $school) {
            $targets[] = ['school_id' => (int) $school->id, 'name' => (string) $school->name, 'school_status' => (string) $school->status];
        }

        foreach ($targets as &$target) {
            $target['streams'] = [];
            foreach (self::STREAMS as $stream) {
                $name = self::name($target['school_id'], $stream);
                $row = $streams[$name] ?? null;
                if ($row !== null && $row->school_id !== $target['school_id']) {
                    $row = null;
                }
                $n = $counts[($target['school_id'] ?? 'platform').':'.$stream] ?? [];
                $sent = array_sum($n);
                $bounced = $n[NotificationDelivery::BOUNCED] ?? 0;
                $complained = $n[NotificationDelivery::COMPLAINED] ?? 0;
                $target['streams'][] = [
                    'stream' => $stream,
                    'configuration_set' => $name,
                    'status' => $row?->status ?? 'not_set_up',
                    'sending_enabled' => $row?->sending_enabled,
                    'last_error' => $row?->last_error,
                    'checked_at' => $row?->checked_at?->format('Y-m-d H:i'),
                    'tenant_status' => $row?->tenant_status,
                    'tenant_status_origin' => $row?->tenant_status_origin,
                    'tenant_status_cause' => $row?->tenant_status_cause,
                    'tenant_status_at' => $row?->tenant_status_at?->format('Y-m-d H:i'),
                    'sent' => $sent,
                    'bounced' => $bounced,
                    'complained' => $complained,
                    'bounce_rate' => $sent > 0 ? $bounced / $sent : null,
                    'complaint_rate' => $sent > 0 ? $complained / $sent : null,
                    'flag' => self::flag($sent, $bounced, $complained),
                ];
            }
        }
        unset($target);

        return ['enabled' => self::enabled(), 'days' => $days, 'targets' => $targets];
    }

    /** null, "review" (at SES's review level) or "pause" (at its pause level), once there is enough mail to judge. */
    public static function flag(int $sent, int $bounced, int $complained): ?string
    {
        if ($sent < max(1, (int) config('mail.streams.report_minimum', 50))) {
            return null;
        }
        $bounce = $bounced / $sent;
        $complaint = $complained / $sent;
        if ($bounce >= (float) config('mail.streams.bounce_pause', 0.10) || $complaint >= (float) config('mail.streams.complaint_pause', 0.005)) {
            return 'pause';
        }
        if ($bounce >= (float) config('mail.streams.bounce_review', 0.05) || $complaint >= (float) config('mail.streams.complaint_review', 0.001)) {
            return 'review';
        }

        return null;
    }

    /** The feedback SNS topic the sets publish to, only when the webhook accepts it. */
    public static function eventTopicArn(): ?string
    {
        $allowed = array_values(array_filter((array) config('services.ses_notifications.topic_arns', []), 'is_string'));
        $topic = (string) (config('mail.streams.event_topic_arn') ?? '');
        if ($topic === '') {
            return $allowed[0] ?? null;
        }

        return in_array($topic, $allowed, true) ? $topic : null;
    }

    /** The school a tenant name stands for, null for the platform, or false when it is not one of ours. */
    private static function tenantOwner(string $tenantName): int|false|null
    {
        $platform = substr(self::name(null, self::ACCOUNT), 0, -strlen('-'.self::ACCOUNT));
        if ($tenantName === $platform) {
            return null;
        }
        $school = substr(self::name(0, self::ACCOUNT), 0, -strlen('0-'.self::ACCOUNT));
        if (str_starts_with($tenantName, $school) && preg_match('/^[1-9][0-9]{0,18}$/', substr($tenantName, strlen($school))) === 1) {
            return (int) substr($tenantName, strlen($school));
        }

        return false;
    }

    private function setUp(MailStream $row, string $topic): MailStream
    {
        $engagement = (bool) (config('mail.streams.engagement_tracking.'.$row->stream) ?? false);
        try {
            $this->ses->ensure($row->configuration_set, [
                'navigateed:school' => $row->school_id === null ? 'platform' : (string) $row->school_id,
                'navigateed:stream' => $row->stream,
            ], $engagement);
            $this->ses->publishEvents($row->configuration_set, $topic);
            $status = $this->ses->status($row->configuration_set);
        } catch (AwsException $e) {
            Log::warning('mail_stream_setup_failed', ['configuration_set' => $row->configuration_set, 'exception' => $e::class]);

            if ($row->status !== MailStream::READY) {
                return $this->failed($row, 'ses_unreachable');
            }
            // A set that was ready stays in use; SES still has it.
            $row->forceFill(['last_error' => 'ses_unreachable', 'checked_at' => now()])->save();

            return $row;
        }
        if ($status === null) {
            return $this->failed($row, 'missing_in_ses');
        }
        $wasReady = $row->status === MailStream::READY;
        $row->forceFill(['status' => MailStream::READY, 'event_destination' => true, 'last_error' => null, 'ready_at' => $row->ready_at ?? now()]);
        $this->record($row, $status);
        if (! $wasReady) {
            $this->audit($row, 'mail_stream.ready', [], ['configuration_set' => $row->configuration_set, 'stream' => $row->stream]);
        }

        return $row;
    }

    /** @param array{sending_enabled: bool, engagement_tracking: bool, optimized_delivery: bool} $status */
    private function record(MailStream $row, array $status): void
    {
        $before = $row->sending_enabled;
        $row->forceFill([
            'sending_enabled' => $status['sending_enabled'],
            'engagement_tracking' => $status['engagement_tracking'],
            'optimized_delivery' => $status['optimized_delivery'],
            // SES answered: a ready set's earlier problem (such as ses_unreachable) is over.
            'last_error' => $row->status === MailStream::READY ? null : $row->last_error,
            'checked_at' => now(),
        ])->save();
        if ($before !== null && $before !== $status['sending_enabled']) {
            $this->audit($row, $status['sending_enabled'] ? 'mail_stream.resumed' : 'mail_stream.paused', ['sending_enabled' => $before], ['sending_enabled' => $status['sending_enabled']]);
        }
    }

    private function failed(MailStream $row, string $error): MailStream
    {
        $row->forceFill(['status' => MailStream::FAILED, 'last_error' => $error, 'checked_at' => now()])->save();

        return $row;
    }

    /**
     * @param  array<string, mixed>  $before
     * @param  array<string, mixed>  $after
     */
    private function audit(MailStream $row, string $action, array $before, array $after): void
    {
        Audit::record($action, null, $before, $after + ['configuration_set' => $row->configuration_set], $row->school_id);
    }
}
