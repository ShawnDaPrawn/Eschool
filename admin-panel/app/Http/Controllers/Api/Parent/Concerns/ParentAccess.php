<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api\Parent\Concerns;

use App\Models\Students;
use App\Services\People\Leavers\LeaverState;
use App\Services\ResponseService;
use Illuminate\Http\Exceptions\HttpResponseException;
use Illuminate\Http\Request;

/**
 * Which child a parent's request is about. Every child-scoped endpoint resolves
 * the child through the signed-in parent's own children. Shared by the parent
 * app's API controllers.
 */
trait ParentAccess
{
    /**
     * The child a request is about, always resolved through the logged-in parent's
     * own children. Inside the CheckChild route group the middleware has already
     * resolved it; elsewhere child_id is looked up here. Ends the request with an
     * error response (thrown as an HttpResponseException; a catch block that hands
     * it to ResponseService::errorResponse() returns it unchanged) when child_id is
     * not one of the parent's children.
     */
    private function resolveChild(Request $request, int $errorCode = 105): Students
    {
        $parent = $request->user()?->parent;
        $child = $request->attributes->get('child');

        if ($parent && $child instanceof Students
            && in_array((int) $parent->id, [(int) $child->father_id, (int) $child->mother_id, (int) $child->guardian_id], true)) {
            app(LeaverState::class)->guardFamilyWrite($child);

            return $child;
        }

        // A scalar id only: an array would make whereKey() match any of several ids.
        $childId = $request->input('child_id');
        $child = ($parent && is_scalar($childId) && ctype_digit((string) $childId))
            ? $parent->children()->whereKey((int) $childId)->first()
            : null;

        if (! $child) {
            throw new HttpResponseException(ResponseService::errorResponse('Invalid Child ID Passed.', null, $errorCode));
        }
        app(LeaverState::class)->guardFamilyWrite($child);

        return $child;
    }

    /**
     * Ids of the logged-in parent's children.
     *
     * @return list<int>
     */
    private function parentChildIds(Request $request): array
    {
        $parent = $request->user()?->parent;

        return $parent ? $parent->children()->pluck('id')->map(fn ($id) => (int) $id)->all() : [];
    }
}
