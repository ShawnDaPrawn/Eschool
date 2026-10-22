<?php

declare(strict_types=1);

namespace App\Services\Platform\Context\Contributors;

use App\Http\Resources\Platform\ChildResource;
use App\Models\Students;
use App\Services\Platform\Context\ContextContributor;
use App\Services\Platform\Context\ContextScope;
use Illuminate\Support\Collection;

/**
 * children and sign_in_methods as Phase 0 answered them: a parent's children
 * at the current school (as father, mother or guardian) and password sign-in.
 *
 * Stream T2 (Identity) owns these keys: it replaces this class with its own
 * contributors (children across schools with student_number, approved and
 * homeroom; sign_in_methods; approval) and removes it from config/context.php.
 */
class IdentityDefaultsContributor implements ContextContributor
{
    public function contribute(ContextScope $scope): array
    {
        return [
            'sign_in_methods' => ['password'],
            'children' => $this->children($scope)->map(fn (Students $child) => new ChildResource($child, $scope->profile))->values(),
        ];
    }

    /**
     * Nobody but a parent has children here.
     *
     * @return Collection<int, Students>
     */
    private function children(ContextScope $scope): Collection
    {
        $user = $scope->user;
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
