<?php

declare(strict_types=1);

namespace App\Services\Platform;

use App\Models\User;
use App\Services\Platform\Context\ContextContributor;
use App\Services\Platform\Context\ContextScope;
use App\Services\Platform\Tenancy\CurrentSchool;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;
use Illuminate\Http\Resources\MissingValue;
use Illuminate\Support\Collection;
use LogicException;

/**
 * What GET /api/v2/me/context answers, composed from the contributors listed
 * in config/context.php (see ContextContributor): the signed-in user with
 * their roles at the current school, their schools, the current school with
 * its year and terms, what is switched on there, and a parent's children.
 *
 * Every app asks for it at start-up, after signing in and on changing school,
 * so it has a budget of 150 ms at p95: a fixed handful of queries whatever the
 * number of children or schools (ContextTest counts them).
 */
class ContextService
{
    public function __construct(private readonly CurrentSchool $currentSchool) {}

    /**
     * @return array<string, mixed>
     */
    public function for(User $user, Request $request): array
    {
        $school = $this->currentSchool->school()
            ?? throw new LogicException('/me/context needs a school: its route group has school.context.');
        $scope = new ContextScope($user, $school, SchoolProfile::current($school), $request);

        $context = [];
        foreach ((array) config('context.contributors', []) as $class) {
            /** @var ContextContributor $contributor */
            $contributor = app($class);
            foreach ($contributor->contribute($scope) as $key => $value) {
                $value = $this->resolve($value, $request);
                $context[$key] = is_array($value) && isset($context[$key]) && is_array($context[$key]) && ! array_is_list($value)
                    ? array_replace_recursive($context[$key], $value)
                    : $value;
            }
        }

        return $context;
    }

    /** Resources become plain arrays, so contributors' objects can be merged. */
    private function resolve(mixed $value, Request $request): mixed
    {
        if ($value instanceof JsonResource) {
            $value = $value->resolve($request);
        } elseif ($value instanceof Collection) {
            $value = $value->all();
        }
        if (is_array($value)) {
            $value = array_filter($value, fn ($item) => ! $item instanceof MissingValue);

            return array_map(fn ($item) => $this->resolve($item, $request), $value);
        }

        return $value;
    }
}
