<?php

declare(strict_types=1);

namespace App\Services\FamilySummary;

use App\Models\Family\FamilySummaryPreference;
use App\Models\User;
use App\Services\Platform\Notifications\QuietHours;
use App\Services\Platform\Tenancy\CurrentSchool;
use Carbon\CarbonImmutable;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

/**
 * A guardian's own choice (Action Plan 5.11): whether they get the daily
 * family summary and at what time, one setting for every school, like their
 * quiet hours. The time must fall outside their quiet hours.
 */
class FamilySummaryPreferences
{
    /** @return array{enabled: bool, send_at: string, timezone: string, revision: int, quiet_hours: array} */
    public function get(User $actor): array
    {
        $fresh = app(FamilySummaryAuthority::class)->guardian($actor);
        $row = FamilySummaryPreference::query()->where('user_id', $fresh->id)->first();
        $quiet = app(QuietHours::class)->get($fresh);

        return [
            'enabled' => (bool) ($row?->enabled ?? false),
            'send_at' => substr((string) ($row?->send_at ?? config('family_summary.default_time', '17:30')), 0, 5),
            'timezone' => (string) ($row?->timezone ?? $this->defaultTimezone()),
            'revision' => (int) ($row?->revision ?? 0),
            'quiet_hours' => ['enabled' => $quiet['enabled'], 'starts_at' => $quiet['starts_at'], 'ends_at' => $quiet['ends_at'], 'timezone' => $quiet['timezone']],
        ];
    }

    /** @param array{enabled: bool, send_at: string, timezone?: ?string} $data */
    public function save(User $actor, array $data): array
    {
        $fresh = app(FamilySummaryAuthority::class)->guardian($actor);
        $timezone = (string) ($data['timezone'] ?? $this->defaultTimezone());
        if (! in_array($timezone, timezone_identifiers_list(), true)) {
            throw ValidationException::withMessages(['timezone' => __('Choose a real time zone.')]);
        }
        if (preg_match('/^([01]\d|2[0-3]):[0-5]\d$/D', (string) $data['send_at']) !== 1) {
            throw ValidationException::withMessages(['send_at' => __('Use a time such as 21:00.')]);
        }
        // The job runs every 15 minutes (routes/console.php), so only quarter
        // hours are always reached on the same local day: 23:50 would fall
        // after the day's last run and never be due. Every real UTC offset is
        // a multiple of 15 minutes, so a local quarter hour is always a run.
        if (! in_array(substr((string) $data['send_at'], 3, 2), self::SLOTS, true)) {
            throw ValidationException::withMessages(['send_at' => __('Choose a time on the quarter hour, such as 17:30.')]);
        }
        $quiet = app(QuietHours::class)->get($fresh);
        if ($quiet['enabled'] && self::within(self::inZone($data['send_at'], $timezone, $quiet['timezone']), $quiet['starts_at'], $quiet['ends_at'])) {
            throw ValidationException::withMessages(['send_at' => $quiet['timezone'] === $timezone
                ? __('Choose a time outside your quiet hours (:start to :end).', ['start' => $quiet['starts_at'], 'end' => $quiet['ends_at']])
                : __('Choose a time outside your quiet hours (:start to :end, :zone).', ['start' => $quiet['starts_at'], 'end' => $quiet['ends_at'], 'zone' => $quiet['timezone']])]);
        }
        DB::transaction(function () use ($fresh, $data, $timezone): void {
            $row = FamilySummaryPreference::query()->where('user_id', $fresh->id)->lockForUpdate()->first();
            $values = ['enabled' => (bool) $data['enabled'], 'send_at' => $data['send_at'].':00', 'timezone' => $timezone];
            if ($row === null) {
                FamilySummaryPreference::query()->create(['user_id' => $fresh->id, 'revision' => 1] + $values);
            } else {
                $row->fill($values);
                if ($row->isDirty()) {
                    $row->revision++;
                    $row->save();
                }
            }
        });

        return $this->get($fresh);
    }

    /** The minutes a summary may be sent at: the job's own runs. */
    public const SLOTS = ['00', '15', '30', '45'];

    /**
     * Today's delivery moment, chosen in one zone, as a clock time in another,
     * so it is compared with quiet hours in the quiet hours' own zone.
     */
    public static function inZone(string $time, string $from, string $to): string
    {
        $to = in_array($to, timezone_identifiers_list(), true) ? $to : 'UTC';

        return CarbonImmutable::now($from)->setTimeFromTimeString(substr($time, 0, 5))->setTimezone($to)->format('H:i');
    }

    /** Whether a clock time falls inside quiet hours (an end before the start is overnight). */
    public static function within(string $time, string $start, string $end): bool
    {
        $time = substr($time, 0, 5);
        $start = substr($start, 0, 5);
        $end = substr($end, 0, 5);

        return $start < $end ? $time >= $start && $time < $end : $time >= $start || $time < $end;
    }

    private function defaultTimezone(): string
    {
        return app(CurrentSchool::class)->school()?->timezone ?: 'Africa/Johannesburg';
    }
}
