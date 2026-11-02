<?php

declare(strict_types=1);

namespace App\Http\Controllers\Platform;

use App\Http\Controllers\Controller;
use App\Services\Platform\Saas\PlatformHome;
use Illuminate\Contracts\View\View;
use Illuminate\Http\Request;

/** HQ: the home page after sign-in (Action Plan 4F). Counts per area the staff member may open. */
class PlatformHomeController extends Controller
{
    public function __invoke(Request $request, PlatformHome $home): View
    {
        return view('saas.home', ['tiles' => $home->tiles($request->user())]);
    }
}
