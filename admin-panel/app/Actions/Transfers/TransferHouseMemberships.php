<?php

declare(strict_types=1);

namespace App\Actions\Transfers;

use App\Models\People\Enrolment;
use App\Models\Recognition\House;
use App\Models\Recognition\HouseMembership;
use App\Models\SessionYear;
use App\Models\StudentSessions;
use App\Models\User;
use App\Services\Recognition\RecognitionAuthority;
use App\Services\Recognition\RecognitionCatalog;
use Carbon\CarbonImmutable;
use Illuminate\Support\Facades\DB;
use Ramsey\Uuid\Uuid;

/**
 * Houses are the exception to "carry the structure, not the people": a
 * learner stays in their house. Each learner in a house at the end of the
 * year, who has a homeroom on the new year's first day (promoted, repeating
 * or placed, so not a leaver) in a homeroom with a grade, joins the same house
 * for the new year from that day. Points are not carried. A new year that already has house memberships
 * is left alone. Specification:
 * engineering/specs/academics/new-year-structures.md (Action Plan 1.19).
 */
class TransferHouseMemberships
{
    public function execute(int $fromSessionId, int $toSessionId): void
    {
        $actor = auth()->user();
        abort_unless($actor instanceof User, 403);
        app(RecognitionAuthority::class)->manage($actor, 'house_points');
        $from = SessionYear::query()->findOrFail($fromSessionId);
        $to = SessionYear::query()->findOrFail($toSessionId);
        $lastDay = CarbonImmutable::parse($from->end_date)->toDateString();
        $firstDay = CarbonImmutable::parse($to->start_date)->toDateString();

        DB::transaction(function () use ($actor, $from, $to, $lastDay, $firstDay) {
            if (HouseMembership::query()->where('session_year_id', $to->id)->exists()) {
                return;
            }
            $houses = House::query()->where('active', true)->pluck('id')->flip();
            // Recognition needs the homeroom's grade; a learner in an ungraded
            // homeroom is left for the school to place in a house by hand.
            // A learner promoted and then marked as leaving keeps the dated
            // enrolment; the leaving decision on the year's row wins.
            $leaving = StudentSessions::query()->where('session_year_id', $to->id)->where('status', 0)->select('student_id');
            $continuing = Enrolment::query()->where('session_year_id', $to->id)->onDate($firstDay)->whereNotIn('learner_id', $leaving)
                ->whereHas('homeroom.class', fn ($q) => $q->whereNotNull('grade_level_id'))->pluck('learner_id')->flip();
            $memberships = HouseMembership::query()->where('session_year_id', $from->id)->where('active', true)
                ->whereDate('starts_on', '<=', $lastDay)->where(fn ($q) => $q->whereNull('ends_on')->orWhereDate('ends_on', '>=', $lastDay))
                ->orderBy('id')->get();
            foreach ($memberships as $membership) {
                if (! $houses->has((int) $membership->house_id) || ! $continuing->has((int) $membership->learner_id)) {
                    continue;
                }
                app(RecognitionCatalog::class)->membership($actor, [
                    // Stable per source membership, so a retried switch cannot add it twice.
                    'operation_id' => Uuid::uuid5(Uuid::NAMESPACE_URL, 'house-carry:'.$membership->id.':'.$to->id)->toString(),
                    'learner_id' => (int) $membership->learner_id, 'house_id' => (int) $membership->house_id, 'session_year_id' => (int) $to->id,
                    'starts_on' => $firstDay, 'ends_on' => null, 'active' => true, 'reason' => 'Carried into the new school year',
                ]);
            }
        });
    }
}
