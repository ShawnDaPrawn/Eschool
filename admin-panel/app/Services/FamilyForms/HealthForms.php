<?php

declare(strict_types=1);

namespace App\Services\FamilyForms;

use App\Models\Platform\ProfileFieldDefinition;
use App\Models\User;
use App\Policies\People\ProfilePolicy;
use App\Services\People\Profiles\ProfileForms;
use Illuminate\Support\Facades\DB;

/** Reuses encrypted profile categories; does not create a competing medical record. */
class HealthForms
{
    private function person(User $actor, int $learner, bool $write = false): User
    {
        $access = app(FamilyFormAccess::class);
        $fresh = $access->actor($actor, $write);
        $person = $access->learner($learner)->user;
        $staff = app(ProfilePolicy::class)->permission($fresh, $write ? 'family-health-manage' : 'family-health-read');
        if (! $staff) {
            $access->guardian($fresh, $learner, $write);
        }

        return $person;
    }

    public function read(User $actor, int $learner): array
    {
        $person = $this->person($actor, $learner);
        $schema = app(ProfileForms::class)->schema($actor, $person, 'learner', category: 'health');
        $schema['categories'] = array_values(array_filter($schema['categories'], fn ($row) => $row['key'] === 'health'));
        $this->person($actor, $learner);

        return $schema;
    }

    public function save(User $actor, int $learner, array $values): array
    {
        return DB::transaction(function () use ($actor, $learner, $values) {
            $person = $this->person($actor, $learner, true);
            $keys = ProfileFieldDefinition::where('profile_type', 'learner')->whereHas('category', fn ($q) => $q->where('key', 'health'))->pluck('key')->all();
            abort_if(array_diff(array_keys($values), $keys) !== [], 422, 'Choose only available health fields.');
            app(ProfileForms::class)->save($actor, $person, 'learner', $values);
            $this->person($actor, $learner, true);

            return $this->read($actor, $learner);
        }, 3);
    }
}
