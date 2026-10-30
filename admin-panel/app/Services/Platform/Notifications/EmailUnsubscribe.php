<?php

declare(strict_types=1);

namespace App\Services\Platform\Notifications;

use App\Models\User;
use App\Services\Platform\Tenancy\CurrentSchool;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\URL;
use Illuminate\Validation\ValidationException;

/**
 * One-click unsubscribe from optional notice emails (Action Plan 2.4,
 * RFC 8058). An email whose notice type the recipient may switch off at that
 * school carries List-Unsubscribe and List-Unsubscribe-Post headers and a
 * footer link. Both lead to a signed link for that person, school and type;
 * using it switches that type's email off for the person, exactly as their own
 * notification settings would, at once. Mandatory notices (account safety,
 * urgent school notices, absence alerts and any type the school requires)
 * never carry it and cannot be switched off through it.
 * engineering/specs/platform/email-unsubscribe.md.
 */
class EmailUnsubscribe
{
    /** How long an unsubscribe link in a sent email keeps working. */
    public const DAYS = 90;

    public const ROUTE = 'notifications.unsubscribe';

    public function __construct(private readonly NotificationRules $rules, private readonly CurrentSchool $currentSchool) {}

    /**
     * The signed link for this email, or null when the person cannot switch
     * this type's email off at this school. Call it in the email's school.
     */
    public function url(User $user, string $type): ?string
    {
        if (! $this->currentSchool->isSet() || $this->currentSchool->isAcrossSchools()) {
            return null;
        }
        $schoolId = $this->currentSchool->id();
        if (! $this->rules->offered($user, $type) || $this->rules->mandatory($user, $type, 'email')) {
            return null;
        }

        return URL::temporarySignedRoute(self::ROUTE, now()->addDays(self::DAYS), ['user' => $user->id, 'school' => $schoolId, 'type' => $type]);
    }

    /**
     * What the link would do now: the school's name, the notice's label, and
     * whether the email is already off or required.
     *
     * @return array{school: string, label: string, state: 'on'|'off'|'required'}
     */
    public function preview(int $userId, int $schoolId, string $type): array
    {
        return $this->currentSchool->run($schoolId, function () use ($userId, $type): array {
            $user = $this->user($userId);
            $state = match (true) {
                ! $this->rules->offered($user, $type) || $this->rules->mandatory($user, $type, 'email') => 'required',
                $this->rules->allows($user, $type, 'email') => 'on',
                default => 'off',
            };

            return ['school' => $this->currentSchool->school()?->name ?? '', 'label' => $this->label($type), 'state' => $state];
        });
    }

    /**
     * Switches the type's email off for the person, as their own choice.
     *
     * @return 'unsubscribed'|'already'|'required'
     */
    public function apply(int $userId, int $schoolId, string $type): string
    {
        return $this->currentSchool->run($schoolId, function () use ($userId, $schoolId, $type): string {
            $user = $this->user($userId);
            if (! $this->rules->offered($user, $type) || $this->rules->mandatory($user, $type, 'email')) {
                return 'required';
            }
            if (! $this->rules->allows($user, $type, 'email')) {
                return 'already';
            }
            try {
                $this->rules->choose($user, $type, 'email', false);
            } catch (ValidationException) {
                return 'required';
            }
            // Ids only: the address and the notice's content stay out of the log.
            Log::info('email_unsubscribed', ['user_id' => $userId, 'school_id' => $schoolId, 'type' => $type]);

            return 'unsubscribed';
        });
    }

    private function user(int $userId): User
    {
        $user = User::acrossSchools()->whereKey($userId)->first();
        abort_if($user === null, 404);

        return $user;
    }

    private function label(string $type): string
    {
        $definition = config('notification_types', [])[$type] ?? null;

        return is_array($definition) ? __((string) ($definition['label'] ?? $type)) : $type;
    }
}
