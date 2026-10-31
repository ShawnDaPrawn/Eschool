<?php

declare(strict_types=1);

namespace App\Http\Controllers\Platform;

use App\Http\Controllers\Controller;
use App\Http\Requests\Saas\CheckMailStreamsRequest;
use App\Models\Platform\MailStream;
use App\Services\Platform\Mail\MailStreams;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;

/** HQ: each school's SES streams, their status and bounce and complaint rates (Action Plan 2.3). */
class PlatformMailStreamsController extends Controller
{
    public function __construct(private readonly MailStreams $streams) {}

    public function index(): View
    {
        return view('saas.mail-streams', $this->streams->report());
    }

    public function check(CheckMailStreamsRequest $request): RedirectResponse
    {
        $schoolId = $request->validated('school_id');
        $rows = $this->streams->ensureForHq($schoolId === null ? null : (int) $schoolId, $request->user());
        $ready = count(array_filter($rows, fn (MailStream $row) => $row->status === MailStream::READY));

        return redirect()->route('saas.mail-streams')->with('success', $ready === count(MailStreams::STREAMS)
            ? 'Both streams are set up in SES and checked.'
            : 'Not every stream could be set up: see the problem shown against it.');
    }
}
