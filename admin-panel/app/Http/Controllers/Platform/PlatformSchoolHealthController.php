<?php

declare(strict_types=1);

namespace App\Http\Controllers\Platform;

use App\Http\Controllers\Controller;
use App\Services\Platform\Saas\PlatformSchoolHealth;
use App\Services\Platform\Saas\PlatformSchools;
use Illuminate\Contracts\View\View;
use Illuminate\Http\Request;

/** HQ: school health and usage, per school and across schools (Action Plan SA.10). Read-only. */
class PlatformSchoolHealthController extends Controller
{
    public function __construct(private readonly PlatformSchoolHealth $health) {}

    public function index(Request $request): View
    {
        $sort = $request->query('sort');

        return view('saas.health', $this->health->overview($request->user(), is_string($sort) ? $sort : 'flags'));
    }

    public function show(Request $request, int $school): View
    {
        $this->health->authorise($request->user());
        $row = app(PlatformSchools::class)->find($school);
        abort_if($row === null, 404);

        return view('saas.school-health', ['school' => $row, 'health' => $this->health->school($request->user(), $school)]);
    }
}
