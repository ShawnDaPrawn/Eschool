<?php

declare(strict_types=1);

namespace App\Http\Controllers\Panel\OpenStandards;

use App\Http\Controllers\Controller;
use App\Http\Requests\OpenStandards\OneRosterExportRequest;
use App\Services\OpenStandards\OneRoster\OneRosterExports;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response;

/** The school's OneRoster 1.2 export page (Action Plan 5.9). */
class OneRosterController extends Controller
{
    public function __construct(private readonly OneRosterExports $exports) {}

    public function index(Request $request): View
    {
        return view('open-standards.oneroster', $this->exports->page($request->user()));
    }

    public function store(OneRosterExportRequest $request): RedirectResponse
    {
        $this->exports->request($request->user(), ['reason' => (string) $request->validated('reason'), 'include_demographics' => $request->boolean('include_demographics')]);

        return redirect()->route('open-standards.index')->with('success', __('The export is being prepared. Reload this page in a minute to download it.'));
    }

    /** The signed link: bound to the person it was made for, and short-lived. */
    public function download(Request $request, int $export): Response
    {
        $file = $this->exports->download($request->user(), $export, (int) $request->query('reader'));

        return response($file['bytes'], 200, [
            'Content-Type' => 'application/zip',
            'Content-Disposition' => 'attachment; filename="'.$file['name'].'"',
            'Cache-Control' => 'no-store, private',
            'X-Content-Type-Options' => 'nosniff',
        ]);
    }
}
