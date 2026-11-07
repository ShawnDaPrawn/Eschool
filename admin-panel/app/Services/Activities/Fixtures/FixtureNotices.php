<?php

declare(strict_types=1);

namespace App\Services\Activities\Fixtures;

use App\Models\Activities\FixtureSelection;
use App\Models\Activities\FixtureSide;
use App\Models\Parents;
use App\Models\Platform\GuardianLink;
use App\Models\Shared\SharedFixture;
use App\Models\Students;
use App\Models\User;
use App\Services\Activities\CommunityAuthority;
use App\Services\Platform\Modules\ModuleGate;
use App\Services\Platform\Notifications\Notifier;
use App\Services\Platform\Notifications\PrivateNoticeDelivery;
use App\Services\Platform\Notifications\PrivateNoticeScope;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Symfony\Component\HttpKernel\Exception\HttpExceptionInterface;

/**
 * A private notice to the guardians of the learners on a school's team sheet
 * when a fixture's result is confirmed. It says only that a result is ready;
 * the app reads the result for the guardian's own child. Each school notifies
 * its own families, inside its own school.
 */
class FixtureNotices implements PrivateNoticeDelivery, PrivateNoticeScope
{
    public const TYPE = 'fixtures.result_confirmed';

    public const LINK = '/activities/fixtures';

    /** In the school whose families are told (Fixtures runs it there). */
    public function confirmed(SharedFixture $fixture): void
    {
        if (! app(ModuleGate::class)->allows('groups')) {
            return;
        }
        $side = FixtureSide::query()->where('shared_fixture_id', $fixture->id)->first();
        if ($side === null) {
            return;
        }
        $recipients = [];
        foreach (FixtureSelection::query()->where('shared_fixture_side_id', $side->id)->pluck('learner_id') as $learnerId) {
            $recipients = [...$recipients, ...$this->guardians((int) $learnerId)];
        }
        $recipients = array_values(array_unique($recipients));
        if ($recipients === []) {
            return;
        }
        $notifier = app(Notifier::class);
        $notice = $notifier->makeNotice(self::TYPE, __('Match result'), __('A result is ready for your child\'s team. Open the app to see it.'), self::LINK);
        $notice->delivery_payload = ['link' => self::LINK, 'fixture_id' => (int) $fixture->id, 'result_revision' => (int) $fixture->result_revision];
        $notice->save();
        $notifier->assign($notice, $recipients);
    }

    /** The guardians (not other families) who may read this learner's fixtures today. */
    private function guardians(int $learnerId): array
    {
        $learner = Students::query()->find($learnerId);
        if ($learner === null) {
            return [];
        }
        $legacy = array_values(array_filter([(int) $learner->father_id, (int) $learner->mother_id, (int) $learner->guardian_id]));
        $candidates = GuardianLink::query()->where('learner_id', $learnerId)->where('status', 'active')->pluck('guardian_user_id')->all();
        if ($legacy !== []) {
            $candidates = [...$candidates, ...Parents::query()->whereIn('id', $legacy)->pluck('user_id')->all()];
        }
        $ids = [];
        foreach (User::query()->whereIn('id', array_unique($candidates))->get() as $user) {
            if (in_array($learnerId, $this->learners($user), true)) {
                $ids[] = (int) $user->id;
            }
        }

        return $ids;
    }

    /** @return list<int> */
    private function learners(User $actor): array
    {
        try {
            return app(CommunityAuthority::class)->learnerIds($actor);
        } catch (HttpExceptionInterface|ModelNotFoundException) {
            return [];
        }
    }

    public function apply(Builder $notifications, User $actor): void
    {
        $payload = $notifications->qualifyColumn('delivery_payload');
        $safe = "IF(JSON_VALID($payload), $payload, '{}')";
        $learners = $this->learners($actor);
        $notifications->where(function (Builder $q) use ($learners, $safe) {
            $q->whereRaw('1 = 0');
            if ($learners === []) {
                return;
            }
            $sides = FixtureSide::query()->whereIn('id', FixtureSelection::query()->whereIn('learner_id', $learners)->select('shared_fixture_side_id'))
                ->whereRaw("CAST(shared_fixture_sides.shared_fixture_id AS CHAR) = JSON_UNQUOTE(JSON_EXTRACT($safe, '$.fixture_id'))");
            $fixture = SharedFixture::query()->where('result_state', 'confirmed')->where('status', 'scheduled')
                ->whereRaw("CAST(shared_fixtures.id AS CHAR) = JSON_UNQUOTE(JSON_EXTRACT($safe, '$.fixture_id'))")
                ->whereRaw("CAST(shared_fixtures.result_revision AS CHAR) = JSON_UNQUOTE(JSON_EXTRACT($safe, '$.result_revision'))");
            $q->orWhere(fn (Builder $branch) => $branch->where('type', self::TYPE)->whereRaw("JSON_UNQUOTE(JSON_EXTRACT($safe, '$.link')) = ?", [self::LINK])
                ->whereExists($sides->selectRaw('1')->toBase())->whereExists($fixture->selectRaw('1')->toBase()));
        });
    }

    public function allows(User $actor, string $type, array $payload): bool
    {
        if ($type !== self::TYPE || ($payload['link'] ?? null) !== self::LINK) {
            return false;
        }
        foreach (['fixture_id', 'result_revision'] as $key) {
            if (filter_var($payload[$key] ?? null, FILTER_VALIDATE_INT, ['options' => ['min_range' => 1]]) === false) {
                return false;
            }
        }
        $fixture = SharedFixture::query()->find((int) $payload['fixture_id']);
        if ($fixture === null || $fixture->result_state !== 'confirmed' || $fixture->status !== 'scheduled' || $fixture->result_revision !== (int) $payload['result_revision']) {
            return false;
        }
        $side = FixtureSide::query()->where('shared_fixture_id', $fixture->id)->first();

        return $side !== null && FixtureSelection::query()->where('shared_fixture_side_id', $side->id)->whereIn('learner_id', $this->learners($actor))->exists();
    }
}
