<?php

declare(strict_types=1);

namespace App\Http\Controllers\Panel\Groups;

use App\Http\Controllers\Controller;
use App\Models\Platform\SchoolGroup;
use App\Services\Platform\Groups\SchoolGroupAccess;
use App\Services\Platform\Groups\SchoolGroupDashboard;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response;

/**
 * The group administrator's dashboard (Action Plan 5.7): counts and shares
 * across the group's current schools. No school context: the page works for
 * the group, and each school's figures are read inside that school.
 */
class GroupDashboardController extends Controller
{
    public function index(Request $request): RedirectResponse|Response
    {
        $groups = app(SchoolGroupAccess::class)->groups($request->user());
        abort_if($groups === [], 403);
        if (count($groups) === 1) {
            return redirect()->route('group.show', array_key_first($groups));
        }
        $names = SchoolGroup::query()->whereIn('id', array_keys($groups))->orderBy('name')->pluck('name', 'id')->all();

        return response()->view('groups.index', ['groups' => $names])->header('Cache-Control', 'private, no-store');
    }

    public function show(Request $request, int $group): Response
    {
        $access = app(SchoolGroupAccess::class);
        $access->authorise($request->user(), $group);
        $dashboard = app(SchoolGroupDashboard::class)->read($group);
        // Read again after the figures: a grant revoked meanwhile shows nothing.
        $access->authorise($request->user(), $group);

        return response()->view('groups.dashboard', ['dashboard' => $dashboard, 'others' => count($access->groups($request->user())) > 1])->header('Cache-Control', 'private, no-store');
    }
}
