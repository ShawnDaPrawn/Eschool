<?php

declare(strict_types=1);

namespace App\Services\Activities\Fixtures;

use App\Models\Activities\AcademicAwardLink;
use App\Models\Activities\AcademicResult;
use App\Models\Activities\FixtureSelection;
use App\Models\Shared\SharedParticipant;
use App\Models\User;
use App\Services\Activities\CommunityAuthority;
use App\Services\Platform\Audit\Audit;
use App\Services\Platform\Shared\SharedRecords;
use App\Services\Recognition\AwardPresentations;
use App\Services\Recognition\RecognitionAuthority;
use Illuminate\Support\Facades\Validator;
use Ramsey\Uuid\Uuid;

/**
 * Links an academic competition to the school's own awards (Action Plan 5.3):
 * a manager presents one of the school's award definitions to some of its own
 * entrants, for taking part or for a placing. Each presentation goes through
 * the awards module's own flow (AwardPresentations::present), so its category
 * authority, eligibility, current-year, approval and notice rules all apply.
 * A school can only ever award learners on its own team sheet.
 */
class AcademicAwards
{
    public function __construct(private SharedRecords $shared, private CommunityAuthority $access, private AcademicContests $contests) {}

    public function link(User $actor, int $id, array $input): array
    {
        $this->access->manage($actor);
        $data = Validator::make($input, AcademicContests::rules('awards'))->validate();
        $authority = function () use ($actor, $id) {
            $this->access->manage($actor);
            [, $part, $side] = $this->contests->access($actor, $id, true);
            app(RecognitionAuthority::class)->staff($actor, 'awards', true);
            abort_unless($part->state === SharedParticipant::ACCEPTED && $side !== null, 409, 'Accept the competition first.');
        };
        $this->shared->once($actor, 'academic-awards:'.$id, $data, $authority, function () use ($actor, $id, $data) {
            [$fixture, , $side] = $this->contests->access($actor, $id, true);
            abort_unless($this->shared->open(Fixtures::TYPE, $id), 409, 'Waiting for the other school to accept the competition.');
            abort_if($data['presented_on'] < $fixture->on_date->toDateString(), 422, 'Present the award on or after the day of the competition.');
            $sheet = FixtureSelection::query()->where('shared_fixture_side_id', $side->id)->pluck('learner_id')->map(fn ($v) => (int) $v)->all();
            $learners = array_map('intval', $data['learner_ids']);
            abort_unless(array_diff($learners, $sheet) === [], 422, 'Choose learners from your own entrants.');
            if ($data['basis'] === 'placing') {
                abort_unless($fixture->result_state === 'confirmed', 409, 'A placing award needs the confirmed result.');
                $placed = $this->placed($id, $sheet, $fixture->winner, (string) $this->shared->requireHere(Fixtures::TYPE, $id)->role);
                abort_unless(array_diff($learners, $placed) === [], 422, 'Choose entrants who placed: a winning team, or a place recorded in your submitted result.');
            }
            $event = $this->event((string) $fixture->kind, (string) ($fixture->motion ?: $fixture->discipline), (string) $data['basis']);
            $year = (int) app(RecognitionAuthority::class)->currentYear()->id;
            $links = [];
            foreach ($learners as $learner) {
                $existing = AcademicAwardLink::query()->where('shared_fixture_id', $id)->where('learner_id', $learner)->where('basis', $data['basis'])->first();
                if ($existing !== null) {
                    $links[] = (int) $existing->id;

                    continue;
                }
                // The awards module's own flow, under an operation id derived from this one.
                $presentation = app(AwardPresentations::class)->present($actor, ['operation_id' => Uuid::uuid5(Uuid::NAMESPACE_URL, 'academic-award:'.$data['operation_id'].':'.$learner)->toString(),
                    'learner_id' => $learner, 'session_year_id' => $year, 'award_definition_id' => (int) $data['award_definition_id'], 'award_nomination_id' => null,
                    'presented_on' => $data['presented_on'], 'event' => $event]);
                $link = AcademicAwardLink::query()->create(['shared_fixture_id' => $id, 'learner_id' => $learner, 'basis' => $data['basis'], 'award_presentation_id' => $presentation->id, 'linked_by' => $actor->id]);
                Audit::record('academic.award_linked', $link, [], ['shared_fixture_id' => $id, 'basis' => $link->basis, 'award_presentation_id' => (int) $presentation->id]);
                $links[] = (int) $link->id;
            }

            return ['id' => $id, 'links' => $links];
        });

        return $this->contests->view($actor, $id);
    }

    /**
     * The entrants who placed: everyone on a winning team, and anyone with a
     * place in this school's submitted result.
     *
     * @param  list<int>  $sheet
     * @return list<int>
     */
    private function placed(int $id, array $sheet, ?string $winner, string $role): array
    {
        if ($winner === $role) {
            return $sheet;
        }
        $result = AcademicResult::query()->where('shared_fixture_id', $id)->first();
        if ($result === null || $result->state !== AcademicResult::SUBMITTED) {
            return [];
        }

        return array_values(array_map(fn ($e) => (int) $e['learner_id'], array_filter((array) $result->entrants, fn ($e) => ($e['place'] ?? null) !== null)));
    }

    private function event(string $kind, string $topic, string $basis): string
    {
        $label = match ($kind) {
            'debate' => __('Debate'), 'quiz' => __('Quiz'), 'speech' => __('Speech contest'), default => __('Competition')
        };

        return mb_substr($label.': '.$topic.' · '.($basis === 'placing' ? __('Placed') : __('Took part')), 0, 191);
    }
}
