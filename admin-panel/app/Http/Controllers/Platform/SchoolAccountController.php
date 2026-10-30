<?php

declare(strict_types=1);

namespace App\Http\Controllers\Platform;

use App\Http\Controllers\Controller;
use App\Http\Requests\Saas\SchoolAccountRequest;
use App\Services\Platform\Saas\PlatformSchoolAccounts;
use App\Services\Platform\Saas\PlatformSchools;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;

/** HQ's record of one customer school: profile, stage, contacts, contracts and timeline. */
class SchoolAccountController extends Controller
{
    public function __construct(private readonly PlatformSchoolAccounts $accounts) {}

    public function show(int $school): View
    {
        $row = app(PlatformSchools::class)->find($school);
        abort_if($row === null, 404);

        return view('saas.account', ['school' => $row] + $this->accounts->show($school));
    }

    public function save(SchoolAccountRequest $request, int $school): RedirectResponse
    {
        $this->accounts->saveAccount($school, $request->validated(), $request->user());

        return $this->back($school, __('School record saved.'));
    }

    public function contact(SchoolAccountRequest $request, int $school, ?int $contact = null): RedirectResponse
    {
        $this->accounts->saveContact($school, $contact, $request->validated(), $request->user());

        return $this->back($school, __('Contact saved.'));
    }

    public function removeContact(SchoolAccountRequest $request, int $school, int $contact): RedirectResponse
    {
        $this->accounts->removeContact($school, $contact, $request->user());

        return $this->back($school, __('Contact removed.'));
    }

    public function contract(SchoolAccountRequest $request, int $school): RedirectResponse
    {
        $this->accounts->recordContract($school, $request->validated(), $request->user());

        return $this->back($school, __('Contract recorded.'));
    }

    public function note(SchoolAccountRequest $request, int $school): RedirectResponse
    {
        $this->accounts->addNote($school, $request->validated(), $request->user());

        return $this->back($school, __('Note added.'));
    }

    private function back(int $school, string $message): RedirectResponse
    {
        return redirect()->route('saas.schools.account', $school)->with('success', $message);
    }
}
