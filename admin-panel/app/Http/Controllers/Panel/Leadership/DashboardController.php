<?php

declare(strict_types=1);

namespace App\Http\Controllers\Panel\Leadership;

use App\Http\Controllers\Controller;
use App\Http\Requests\Leadership\DashboardRequest;
use App\Models\SessionYear;
use App\Services\Leadership\LeadershipDashboard;
use Illuminate\Http\Response;

class DashboardController extends Controller
{
    public function index(DashboardRequest $r): Response
    {
        $year = (int) $r->validated('session_year_id', (int) schoolSetting('session_year'));
        $data = app(LeadershipDashboard::class)->read($r->user(), $year, (int) $r->validated('weeks', 8), $r->validated('through'));

        return response()->view('leadership.dashboard', ['dashboard' => $data, 'years' => SessionYear::orderByDesc('start_date')->get(), 'weeks' => (int) $r->validated('weeks', 8)])->header('Cache-Control', 'private, no-store');
    }
}
