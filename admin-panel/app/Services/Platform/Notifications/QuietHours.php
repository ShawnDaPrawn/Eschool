<?php

declare(strict_types=1);

namespace App\Services\Platform\Notifications;

use App\Models\Platform\NotificationQuietHours;
use App\Models\User;
use App\Services\Platform\Tenancy\CurrentSchool;
use Carbon\CarbonImmutable;
use Illuminate\Validation\ValidationException;

/**
 * Quiet hours for push notifications (Action Plan 1.5). While a person's quiet
 * hours run, their push notices wait and are sent when the hours end; email
 * and in-app notices are unaffected. Safety notices are never held
 * (config/notification_types.php `quiet_hours_exempt`). One setting per
 * person, for every school. engineering/specs/platform/quiet-hours.md.
 */
class QuietHours
{
    /** @return array{enabled: bool, starts_at: string, ends_at: string, timezone: string, exempt_types: list<string>} */
    public function get(User $user): array
    {
        $row = NotificationQuietHours::query()->where('user_id', $user->id)->first();

        return [
            'enabled' => (bool) ($row?->enabled ?? false),
            'starts_at' => substr((string) ($row?->starts_at ?? '21:00'), 0, 5),
            'ends_at' => substr((string) ($row?->ends_at ?? '06:30'), 0, 5),
            'timezone' => (string) ($row?->timezone ?? $this->defaultTimezone()),
            'exempt_types' => self::exemptTypes(),
        ];
    }

    /** @param array{enabled: bool, starts_at: string, ends_at: string, timezone?: string|null} $data */
    public function save(User $user, array $data): array
    {
        $timezone = (string) ($data['timezone'] ?? $this->defaultTimezone());
        if (! in_array($timezone, timezone_identifiers_list(), true)) {
            throw ValidationException::withMessages(['timezone' => __('Choose a real time zone.')]);
        }
        foreach (['starts_at', 'ends_at'] as $field) {
            if (preg_match('/^([01]\d|2[0-3]):[0-5]\d$/D', (string) $data[$field]) !== 1) {
                throw ValidationException::withMessages([$field => __('Use a time such as 21:00.')]);
            }
        }
        if ($data['starts_at'] === $data['ends_at']) {
            throw ValidationException::withMessages(['ends_at' => __('Quiet hours must end at a different time from when they start.')]);
        }
        NotificationQuietHours::query()->updateOrCreate(['user_id' => $user->id], [
            'enabled' => (bool) $data['enabled'], 'starts_at' => $data['starts_at'].':00', 'ends_at' => $data['ends_at'].':00', 'timezone' => $timezone,
        ]);

        return $this->get($user);
    }

    /** Legacy push type names (attendance, chat) count as their registered type. */
    public static function exempt(string $type): bool
    {
        return in_array(NotificationTypes::legacy($type), self::exemptTypes(), true);
    }

    /** @return list<string> */
    public static function exemptTypes(): array
    {
        return array_keys(array_filter((array) config('notification_types', []), fn ($definition) => is_array($definition) && ($definition['quiet_hours_exempt'] ?? false) === true));
    }

    /**
     * When a push of this type to this person may be sent: null for now, or
     * the end of their current quiet hours.
     */
    public function holdUntil(int $userId, string $type, ?CarbonImmutable $now = null): ?CarbonImmutable
    {
        if (self::exempt($type)) {
            return null;
        }
        $row = NotificationQuietHours::query()->where('user_id', $userId)->where('enabled', true)->first();
        if ($row === null) {
            return null;
        }
        $zone = in_array($row->timezone, timezone_identifiers_list(), true) ? $row->timezone : 'UTC';
        $local = ($now ?? CarbonImmutable::now())->setTimezone($zone);
        $start = $local->setTimeFromTimeString($row->starts_at);
        $end = $local->setTimeFromTimeString($row->ends_at);
        if ($start < $end) {
            // Same-day hours, such as 13:00 to 14:00.
            return $local >= $start && $local < $end ? $end : null;
        }
        // Overnight, such as 21:00 to 06:30.
        if ($local >= $start) {
            return $end->addDay();
        }

        return $local < $end ? $end : null;
    }

    private function defaultTimezone(): string
    {
        $school = app(CurrentSchool::class)->isSet() ? app(CurrentSchool::class)->school() : null;

        return $school?->timezone ?: 'Africa/Johannesburg';
    }
}
