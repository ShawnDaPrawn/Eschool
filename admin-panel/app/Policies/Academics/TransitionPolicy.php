<?php

declare(strict_types=1);

namespace App\Policies\Academics;

use App\Models\Academics\AcademicExitPackage;
use App\Models\Platform\ProfileFieldDefinition;
use App\Models\Students;
use App\Models\User;
use App\Policies\People\ProfilePolicy;
use App\Services\Academics\AcademicAuthority;

class TransitionPolicy
{
    public function learner(User $actor, int $learner): Students
    {
        app(AcademicAuthority::class)->manage($actor);

        return Students::with('user')->findOrFail($learner);
    }

    public function export(User $actor, int $learner): Students
    {
        $row = $this->learner($actor, $learner);
        $profiles = app(ProfilePolicy::class);
        abort_unless($profiles->permission($profiles->actor($actor), 'profile-sensitive-export'), 403);

        return $row;
    }

    public function package(User $actor, int $id): AcademicExitPackage
    {
        app(AcademicAuthority::class)->manage($actor);
        $row = AcademicExitPackage::where('requested_by', $actor->id)->findOrFail($id);
        $learner = $this->export($actor, (int) $row->learner_id);
        $documents = $row->blueprint['documents'] ?? [];
        if ($documents !== []) {
            $profiles = app(ProfilePolicy::class);
            $context = $profiles->resolve($actor, $learner->user, 'learner');
            foreach ($documents as $document) {
                $definition = ProfileFieldDefinition::where('profile_type', 'learner')->where('type', 'file')->whereHas('category')->findOrFail($document['definition_id']);
                abort_unless($profiles->field($context, $definition), 404);
            }
        }

        return $row;
    }
}
