<?php

declare(strict_types=1);

namespace App\Http\Controllers\Platform;

use App\Http\Controllers\Controller;
use App\Models\Platform\SupportAccessGrant;
use App\Services\Platform\Saas\SupportSessionPages;
use App\Services\Platform\Tenancy\CurrentSchool;
use Illuminate\Contracts\View\View;
use Illuminate\Http\Request;

/** The read-only pages of a support session (SA.9); support.session has already checked the grant and set the school. */
class SupportSessionController extends Controller
{
    public function __construct(private readonly SupportSessionPages $pages) {}

    public function show(int $grant): View
    {
        return view('saas.support-session.overview', $this->pages->overview() + $this->session($grant));
    }

    public function people(Request $request, int $grant): View
    {
        $q = $request->query('q');

        return view('saas.support-session.people', $this->pages->people(is_string($q) ? mb_substr($q, 0, 100) : null) + $this->session($grant));
    }

    public function person(int $grant, int $person): View
    {
        return view('saas.support-session.person', ['person' => $this->pages->person($person)] + $this->session($grant));
    }

    /** The banner every page shows: whose school, read-only, and until when. */
    private function session(int $grant): array
    {
        $current = app(CurrentSchool::class);
        abort_unless($current->supportGrantId() === $grant, 403);
        $row = SupportAccessGrant::query()->findOrFail($grant);

        return ['session' => [
            'grant' => $grant, 'school' => (string) $current->school()?->name,
            'expires_at' => $row->expires_at?->copy()->setTimezone('Africa/Johannesburg')->format('Y-m-d H:i'),
            'emergency' => (bool) $row->emergency,
        ]];
    }
}
