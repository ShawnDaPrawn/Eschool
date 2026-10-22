<?php

declare(strict_types=1);

namespace App\Services\Platform;

use App\Models\SessionYear;
use App\Models\Settings;
use App\Models\Students;
use App\Models\User;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Storage;

/**
 * What GET /api/v2/me/context answers: the signed-in user with their roles
 * and schools, the current school with its year and terms, what is switched
 * on there, and a parent's children.
 *
 * Every app asks for it at start-up, after signing in and on changing school,
 * so it has a budget of 150 ms at p95: a fixed handful of queries whatever the
 * number of children (ContextTest counts them).
 */
class ContextService
{
    /** The settings rows the school profile is built from, read in one query. */
    private const SCHOOL_SETTINGS = [
        'school_name', 'school_short_name', 'school_tagline', 'logo1',
        'theme_color', 'secondary_color', 'time_zone', 'session_year',
    ];

    /**
     * @return array{
     *     user: User,
     *     roles: list<string>,
     *     school: SchoolProfile,
     *     session_year: ?SessionYear,
     *     children: Collection<int, Students>,
     *     modules: list<array<string, mixed>>,
     *     notification_rules: list<array<string, mixed>>,
     *     feature_flags: array<string, bool>,
     *     sign_in_methods: list<string>,
     * }
     */
    public function for(User $user): array
    {
        $school = $this->currentSchool();

        return [
            'user' => $user,
            'roles' => $user->getRoleNames()->values()->all(),
            'school' => $school,
            'session_year' => $this->sessionYear($school),
            'children' => $this->children($user),
            // Phase 1 fills these from school_modules, notification_rules and
            // sign_in_methods; the apps already read them.
            'modules' => [],
            'notification_rules' => [],
            'feature_flags' => [],
            'sign_in_methods' => ['password'],
        ];
    }

    public function currentSchool(): SchoolProfile
    {
        $settings = Settings::query()->whereIn('type', self::SCHOOL_SETTINGS)->pluck('message', 'type');
        $value = fn (string $type): ?string => ($v = trim((string) ($settings[$type] ?? ''))) === '' ? null : $v;

        $timezone = $value('time_zone');
        $logo = $value('logo1');

        return new SchoolProfile(
            id: SchoolProfile::SINGLE_SCHOOL_ID,
            name: $value('school_name') ?? (string) config('app.name'),
            shortName: $value('school_short_name'),
            tagline: $value('school_tagline'),
            crestUrl: $logo === null ? null : url(Storage::url($logo)),
            themeColor: $value('theme_color'),
            secondaryColor: $value('secondary_color'),
            timezone: in_array($timezone, timezone_identifiers_list(), true) ? $timezone : (string) config('app.timezone'),
            sessionYearId: ctype_digit((string) $value('session_year')) ? (int) $value('session_year') : null,
        );
    }

    private function sessionYear(SchoolProfile $school): ?SessionYear
    {
        if ($school->sessionYearId === null) {
            return null;
        }

        return SessionYear::query()
            ->with(['semesters' => fn ($query) => $query->orderBy('start_date')->orderBy('id')])
            ->find($school->sessionYearId);
    }

    /**
     * A parent's children (as father, mother or guardian), each with the
     * fields the child switcher shows. Nobody else has children here.
     *
     * @return Collection<int, Students>
     */
    private function children(User $user): Collection
    {
        if (! $user->hasRole('Parent') || ($parent = $user->parent) === null) {
            return new Collection;
        }

        return $parent->children()
            ->with('user:id,first_name,last_name,image')
            ->orderBy('id')
            ->get(['id', 'user_id'])
            ->filter(fn (Students $child) => $child->user !== null)
            ->values();
    }
}
