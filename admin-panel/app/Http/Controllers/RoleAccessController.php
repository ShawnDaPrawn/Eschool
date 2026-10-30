<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Http\Requests\RoleAccessRequest;
use App\Models\Platform\Role;
use App\Services\Platform\Modules\RoleEditor;
use Illuminate\Http\RedirectResponse;
use Illuminate\View\View;

/** Roles described by module and level (Action Plan 1.13). */
class RoleAccessController extends Controller
{
    public function __construct(private readonly RoleEditor $editor)
    {
        $this->middleware('permission:role-create', ['only' => ['create', 'store']]);
        $this->middleware('permission:role-edit', ['only' => ['edit', 'update']]);
    }

    public function create(): View
    {
        return view('roles.access', ['editor' => $this->editor->describe(null)]);
    }

    public function store(RoleAccessRequest $request): RedirectResponse
    {
        $role = $this->editor->save($request->user(), null, $request->validated());

        return redirect()->route('role-access.edit', $role->id)->with('success', __('Role saved'));
    }

    public function edit(int $role): View
    {
        $record = Role::query()->findOrFail($role);
        abort_unless($this->editor->editable($record), 403);

        return view('roles.access', ['editor' => $this->editor->describe($record)]);
    }

    public function update(RoleAccessRequest $request, int $role): RedirectResponse
    {
        $record = Role::query()->findOrFail($role);
        $this->editor->save($request->user(), $record, $request->validated());

        return redirect()->route('role-access.edit', $record->id)->with('success', __('Role saved'));
    }
}
