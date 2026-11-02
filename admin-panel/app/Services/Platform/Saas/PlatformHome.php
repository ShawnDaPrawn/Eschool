<?php

declare(strict_types=1);

namespace App\Services\Platform\Saas;

use App\Models\Platform\School;
use App\Models\User;
use App\Services\Platform\Notifications\DeliveryLog;
use App\Services\Platform\QueueHealth;

/**
 * The HQ home page (Action Plan 4F): the first page after an HQ sign-in.
 * Each tile reads an existing HQ service and is built only for staff whose
 * role holds that area's ability, so nobody is shown, or costs a query for,
 * an area they cannot open. Counts and school-level facts only: never a
 * learner, guardian, staff member or any other person of a school.
 * A tile whose feature is not built yet (billing, support access, data
 * requests, school health flags) is not offered at all.
 * engineering/specs/platform/hq-home.md.
 */
class PlatformHome
{
    /** The ability each tile needs: the same one its area's page needs. */
    public const TILES = [
        'stages' => 'schools.view',
        'at_risk' => 'schools.view',
        'onboarding' => 'schools.view',
        'incidents' => 'incidents.manage',
        'lurits' => 'identity.review',
        'ai' => 'ai.view',
        'health' => 'health.view',
    ];

    /** Schools whose onboarding checklist is read for the home page; the rest are counted. */
    public const ONBOARDING_LIMIT = 10;

    /** The stages in which a school is still being set up. */
    public const ONBOARDING_STAGES = ['contracted', 'onboarding'];

    /** The lurits list stops at this many open claims. */
    private const LURITS_LIMIT = 200;

    public function __construct(
        private readonly PlatformStaffAccess $access,
        private readonly PlatformSchools $schools,
        private readonly PlatformOnboarding $onboarding,
        private readonly PlatformIncidents $incidents,
        private readonly PlatformLuritsConflicts $lurits,
        private readonly PlatformAiControls $ai,
        private readonly QueueHealth $queue,
    ) {}

    /**
     * The tiles this staff member may see, keyed as TILES, in page order.
     *
     * @return array<string, array<string, mixed>>
     */
    public function tiles(User $actor): array
    {
        $abilities = $this->access->abilities($actor);
        abort_if($abilities === [], 403);

        $allowed = array_keys(array_filter(self::TILES, fn (string $ability): bool => in_array('*', $abilities, true) || in_array($ability, $abilities, true)));
        // The three school tiles share one read of the school list.
        $schools = array_intersect($allowed, ['stages', 'at_risk', 'onboarding']) === [] ? [] : $this->schools->overview();

        $tiles = [];
        foreach ($allowed as $tile) {
            $tiles[$tile] = match ($tile) {
                'stages' => $this->stages($schools),
                'at_risk' => $this->atRisk($schools),
                'onboarding' => $this->onboarding($schools),
                'incidents' => $this->incidents(),
                'lurits' => $this->lurits(),
                'ai' => $this->ai->summary($actor),
                'health' => $this->health(),
            };
        }

        return $tiles;
    }

    /** @param  list<array<string, mixed>>  $schools */
    private function stages(array $schools): array
    {
        $stages = array_fill_keys(PlatformSchoolAccounts::STAGES, 0);
        $statuses = [School::ACTIVE => 0, School::SUSPENDED => 0, School::ARCHIVED => 0];
        foreach ($schools as $school) {
            $stages[$school['stage']] = ($stages[$school['stage']] ?? 0) + 1;
            $statuses[$school['status']] = ($statuses[$school['status']] ?? 0) + 1;
        }

        return ['total' => count($schools), 'stages' => $stages, 'statuses' => $statuses];
    }

    /** @param  list<array<string, mixed>>  $schools */
    private function atRisk(array $schools): array
    {
        $risk = array_values(array_filter($schools, fn (array $school): bool => $school['stage'] === 'at_risk'));

        return ['count' => count($risk), 'schools' => array_map(fn (array $school): array => ['id' => $school['id'], 'name' => $school['name']], $risk)];
    }

    /**
     * Active schools still being set up, each with its checklist progress and
     * the first step not done. The checklist is read for the first
     * ONBOARDING_LIMIT of them only, so the page stays quick.
     *
     * @param  list<array<string, mixed>>  $schools
     */
    private function onboarding(array $schools): array
    {
        $setting = array_values(array_filter($schools, fn (array $school): bool => $school['status'] === School::ACTIVE
            && in_array($school['stage'], self::ONBOARDING_STAGES, true)));
        $rows = [];
        foreach (array_slice($setting, 0, self::ONBOARDING_LIMIT) as $school) {
            $checklist = $this->onboarding->checklist($school['id']);
            $blocking = array_values(array_filter($checklist['steps'], fn (array $step): bool => in_array($step['status'], [PlatformOnboarding::TODO, PlatformOnboarding::STARTED], true)));
            $rows[] = ['id' => $school['id'], 'name' => $school['name'], 'done' => $checklist['done'], 'total' => $checklist['total'],
                'blocking' => count($blocking), 'next' => $blocking[0]['title'] ?? null];
        }

        return ['count' => count($setting), 'schools' => $rows, 'more' => max(0, count($setting) - count($rows))];
    }

    private function incidents(): array
    {
        $open = $this->incidents->list()['open'];
        $count = fn (string $key, array $values): int => count(array_filter($open, fn (array $incident): bool => in_array($incident[$key], $values, true)));

        return [
            'open' => $count('status', ['open']),
            'contained' => $count('status', ['contained']),
            'serious' => $count('severity', ['high', 'critical']),
            'attention' => count(array_filter($open, fn (array $incident): bool => $incident['attention'] !== [])),
            'oldest_days' => $open === [] ? null : max(array_column($open, 'days_open')),
        ];
    }

    private function lurits(): array
    {
        $open = count($this->lurits->list());

        return ['open' => $open, 'capped' => $open >= self::LURITS_LIMIT];
    }

    private function health(): array
    {
        $since = now()->subDay();
        $wait = $this->queue->oldestWaitSeconds();

        return [
            'backlog' => $this->queue->backlog(),
            'oldest_wait_minutes' => $wait === null ? null : intdiv($wait, 60),
            'stalled' => $wait !== null && $wait > $this->queue->maxWaitSeconds(),
            'failed_jobs' => $this->queue->failedJobs($since),
            'deliveries' => DeliveryLog::failuresSince($since),
        ];
    }
}
