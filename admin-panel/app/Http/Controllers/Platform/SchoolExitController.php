<?php

declare(strict_types=1);

namespace App\Http\Controllers\Platform;

use App\Http\Controllers\Controller;
use App\Http\Requests\Platform\SchoolExitRequest;
use App\Services\Platform\Offboarding\SchoolExit;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response;

/** A leaving school's own page (Action Plan SA.11): download the export and confirm receipt. Super Admins only. */
class SchoolExitController extends Controller
{
    public function __construct(private readonly SchoolExit $exit) {}

    public function index(Request $request): View
    {
        return view('platform.school-exit', $this->exit->page($request->user()));
    }

    /** The signed link: bound to the Super Admin it was made for, and short-lived. */
    public function download(Request $request, int $offboarding): Response
    {
        $file = $this->exit->download($request->user(), $offboarding, (int) $request->query('reader'));

        return response($file['bytes'], 200, [
            'Content-Type' => 'application/zip',
            'Content-Disposition' => 'attachment; filename="'.$file['name'].'"',
            'Cache-Control' => 'no-store, private',
            'X-Content-Type-Options' => 'nosniff',
        ]);
    }

    public function confirm(SchoolExitRequest $request, int $offboarding): RedirectResponse
    {
        $this->exit->confirm($request->user(), $offboarding);

        return redirect()->route('school-exit.index')->with('success', __('Thank you. Receipt of the export is recorded.'));
    }
}
