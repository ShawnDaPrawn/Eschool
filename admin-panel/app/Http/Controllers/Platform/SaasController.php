<?php

declare(strict_types=1);

namespace App\Http\Controllers\Platform;

use App\Http\Controllers\Controller;
use App\Http\Requests\Saas\SchoolModuleRequest;
use App\Http\Requests\Saas\SchoolPackageRequest;
use App\Http\Requests\Saas\SchoolStatusRequest;
use App\Http\Requests\Saas\StoreSchoolRequest;
use App\Services\Platform\Identity\StaffInvitations;
use App\Services\Platform\Saas\PlatformSchools;
use App\Services\Platform\Saas\PlatformSupportDesk;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

/**
 * The platform admin area, /saas (the platform.staff middleware): platform
 * staff see the schools on the install, set one up with its first
 * administrator, choose its package and modules, and suspend or restore it.
 * It reads no person of any school. See engineering/specs/platform/saas-panel.md.
 */
class SaasController extends Controller
{
    public function __construct(private readonly PlatformSchools $schools) {}

    public function index(): View
    {
        return view('saas.index', ['schools' => $this->schools->overview()]);
    }

    public function create(): View
    {
        return view('saas.create', $this->formChoices());
    }

    /** The administrator is emailed their link; it is also shown once here, never flashed. */
    public function store(StoreSchoolRequest $request): View
    {
        $created = $this->schools->create($request->validated(), $request->user());

        return view('saas.created', ['school' => $created['school'], 'admin' => $created['admin'], 'invitationUrl' => $created['invitation_url']]);
    }

    public function resendInvitation(Request $request, int $school): RedirectResponse
    {
        abort_if($this->schools->find($school) === null, 404);
        $sent = app(StaffInvitations::class)->resendAdministrator($school, $request->user());

        return redirect()->route('saas.schools.show', $school)->with('success', $sent ? __('Invitation sent again.') : __('The administrator has already accepted their invitation.'));
    }

    public function show(Request $request, int $school): View
    {
        $row = $this->schools->find($school);
        abort_if($row === null, 404);

        return view('saas.show', [
            'school' => $row,
            'pendingAdministrator' => app(StaffInvitations::class)->pendingAdministrator($school) !== null,
            'modules' => $this->schools->modules($school),
            'packages' => array_keys($this->schools->packages()),
            'support' => app(PlatformSupportDesk::class)->forSchool($school),
        ]);
    }

    public function updatePackage(SchoolPackageRequest $request, int $school): RedirectResponse
    {
        $this->schools->changePackage($school, $request->validated('package_key'), $request->user());

        return redirect()->route('saas.schools.show', $school)->with('success', __('Package updated.'));
    }

    public function updateStatus(SchoolStatusRequest $request, int $school): RedirectResponse
    {
        $this->schools->changeStatus($school, $request->validated('status'), $request->validated('reason'), $request->user());

        return redirect()->route('saas.schools.show', $school)->with('success', __('School status updated.'));
    }

    public function updateModule(SchoolModuleRequest $request, int $school, string $module): RedirectResponse
    {
        $this->schools->changeModule($school, $module, $request->boolean('enabled'), $request->user());

        return redirect()->route('saas.schools.show', $school)->with('success', __('Module updated.'));
    }

    /** @return array<string, mixed> */
    private function formChoices(): array
    {
        return ['packages' => array_keys($this->schools->packages()), 'timezones' => timezone_identifiers_list(), 'defaultTimezone' => 'Africa/Johannesburg'];
    }
}
