<?php

declare(strict_types=1);

namespace App\Services\Wellbeing;

use App\Models\User;
use App\Models\Wellbeing\CareCase;
use App\Models\Wellbeing\CaseAssignment;
use App\Models\Wellbeing\Category;
use App\Models\Wellbeing\TeamAssignment;
use Illuminate\Support\Facades\Validator;

class WellbeingSettings
{
    public const EMERGENCY_GUIDANCE = 'If anyone is in immediate danger, contact local emergency services or a trusted adult now. Do not wait for an app response.';

    public function __construct(private WellbeingAccess $access, private WellbeingOperations $operations) {}

    public function categories(): array
    {
        $this->access->environment();

        return ['photo_constraints' => ['maximum_bytes' => 4194304, 'maximum_photos' => IncidentPhotos::LIMIT, 'allowed_mimes' => ['image/jpeg', 'image/png']], 'emergency_guidance' => self::EMERGENCY_GUIDANCE, 'categories' => Category::where('active', true)->orderBy('name')->get(['id', 'name', 'emergency_guidance'])->toArray()];
    }

    public function saveCategory(User $actor, ?int $id, array $input): array
    {
        $this->access->configure($actor, true);
        $data = Validator::make($input, WellbeingRules::rules('category-save'))->validate();
        $authority = function () use ($actor, $id) {
            $this->access->configure($actor, true);
            if ($id !== null) {
                Category::findOrFail($id);
            }
        };

        return $this->operations->once($actor, 'category:'.($id ?? 'new'), $data, $authority, function () use ($actor, $id, $data) {
            $row = $id === null ? new Category : Category::findOrFail($id);
            $this->operations->revision($row, $data);
            $row->fill(['name' => trim(strip_tags($data['name'])), 'emergency_guidance' => trim(strip_tags($data['emergency_guidance'])), 'active' => $data['active'], 'revision' => $data['revision'] + 1])->save();
            $this->operations->event($actor, 'wellbeing.category_saved', $row);

            return $row->only(['id', 'name', 'emergency_guidance', 'active', 'revision']);
        });
    }

    public function assignTeam(User $actor, int $categoryId, ?int $id, array $input): array
    {
        return $this->assignment($actor, 'team', $categoryId, $id, $input);
    }

    public function assignCase(User $actor, int $caseId, ?int $id, array $input): array
    {
        return $this->assignment($actor, 'case', $caseId, $id, $input);
    }

    private function assignment(User $actor, string $kind, int $ownerId, ?int $id, array $input): array
    {
        $this->access->configure($actor, true);
        $data = Validator::make($input, WellbeingRules::rules('assignment-save'))->validate();
        $authority = function () use ($actor, $kind, $ownerId, $id, $data) {
            $this->access->configure($actor, true);
            if ($data['active']) {
                $this->access->professional(User::findOrFail($data['user_id']));
            } else {
                abort_if($id === null, 422, 'Cancel an existing assignment.');
            }
            if ($kind === 'team') {
                Category::findOrFail($ownerId);
                if ($id !== null) {
                    TeamAssignment::where('category_id', $ownerId)->findOrFail($id);
                }
            } else {
                // Assignment administration selects metadata only; no case entries.
                CareCase::select(['id', 'learner_id'])->findOrFail($ownerId);
                if ($id !== null) {
                    CaseAssignment::where('support_case_id', $ownerId)->findOrFail($id);
                }
            }
        };

        return $this->operations->once($actor, $kind.':'.$ownerId.':'.($id ?? 'new'), $data, $authority, function () use ($actor, $kind, $ownerId, $id, $data) {
            $field = $kind === 'team' ? 'category_id' : 'support_case_id';
            $class = $kind === 'team' ? TeamAssignment::class : CaseAssignment::class;
            $row = $id === null ? new $class : $class::where($field, $ownerId)->findOrFail($id);
            $this->operations->revision($row, $data);
            abort_if($row->exists && (int) $row->user_id !== (int) $data['user_id'], 422, 'Create a new dated assignment for a different professional.');
            $row->fill([$field => $ownerId, 'user_id' => $data['user_id'], 'starts_on' => $data['starts_on'], 'ends_on' => $data['ends_on'], 'active' => $data['active'], 'reason' => $data['reason'], 'revision' => $data['revision'] + 1])->save();
            $this->operations->event($actor, 'wellbeing.'.$kind.'_assignment_saved', $row);

            return $row->only(['id', $field, 'user_id', 'active', 'revision']) + ['starts_on' => $row->starts_on->toDateString(), 'ends_on' => $row->ends_on->toDateString()];
        });
    }

    public function openCase(User $actor, array $input): array
    {
        $this->access->configure($actor, true);
        $data = Validator::make($input, WellbeingRules::rules('case-open'))->validate();
        $authority = function () use ($actor, $data) {
            $this->access->configure($actor, true);
            $this->access->learner($data['learner_id']);
        };

        return $this->operations->once($actor, 'case-open', $data, $authority, function () use ($actor, $data) {
            $row = CareCase::create(['learner_id' => $data['learner_id'], 'opened_by' => $actor->id, 'opened_at' => now(), 'status' => 'open', 'revision' => 1]);
            $this->operations->event($actor, 'wellbeing.case_opened', $row);

            return $row->only(['id', 'learner_id', 'status', 'opened_at', 'revision']);
        });
    }

    public function configuration(User $actor): array
    {
        $this->access->configure($actor);

        return ['categories' => Category::orderBy('name')->get()->map(fn ($row) => $row->only(['id', 'name', 'emergency_guidance', 'active', 'revision']))->all(),
            'team_assignments' => TeamAssignment::orderBy('id')->get()->map(fn ($row) => $row->only(['id', 'category_id', 'user_id', 'starts_on', 'ends_on', 'active', 'revision']))->all(),
            'case_assignments' => CaseAssignment::orderBy('id')->get()->map(fn ($row) => $row->only(['id', 'support_case_id', 'user_id', 'starts_on', 'ends_on', 'active', 'revision']))->all(),
            'case_metadata' => CareCase::orderBy('id')->get(['id', 'learner_id', 'status', 'opened_at', 'revision'])->toArray()];
    }
}
