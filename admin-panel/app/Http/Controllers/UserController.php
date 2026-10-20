<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use App\Models\User;
use Spatie\Permission\Models\Role;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rule;

/**
 * Generic user management. There are no user-* permissions, so the whole
 * controller is limited to the Super Admin role.
 */
class UserController extends Controller
{
    private const SUPER_ADMIN = 'Super Admin';

    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function index(Request $request)
    {
        if (! $this->actorIsSuperAdmin()) {
            return $this->noPermission();
        }

        $data = User::orderBy('id','DESC')->paginate(5);
        return view('users.index',compact('data'))
            ->with('i', ($request->input('page', 1) - 1) * 5);
    }

    /**
     * Show the form for creating a new resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function create()
    {
        if (! $this->actorIsSuperAdmin()) {
            return $this->noPermission();
        }

        $roles = array_combine($this->assignableRoleNames(), $this->assignableRoleNames());
        return view('users.create',compact('roles'));
    }

    /**
     * Store a newly created resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */
    public function store(Request $request)
    {
        if (! $this->actorIsSuperAdmin()) {
            return $this->noPermission();
        }

        $this->validate($request, [
            'name' => 'required|string|max:255',
            'email' => 'required|email|unique:users,email',
            'password' => 'required|min:8|same:confirm-password',
            'roles' => 'required|array',
            'roles.*' => ['string', Rule::in($this->assignableRoleNames())],
        ]);

        $user = User::create(array_merge($this->splitName((string) $request->input('name')), [
            'email' => $request->input('email'),
            'password' => Hash::make((string) $request->input('password')),
        ]));
        $user->assignRole($request->input('roles'));

        return redirect()->route('users.index')
                        ->with('success',trans('data_store_successfully'));
    }

    /**
     * Display the specified resource.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function show($id)
    {
        if (! $this->actorIsSuperAdmin()) {
            return $this->noPermission();
        }

        $user = User::findOrFail($id);
        return view('users.show',compact('user'));
    }

    /**
     * Show the form for editing the specified resource.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function edit($id)
    {
        if (! $this->actorIsSuperAdmin()) {
            return $this->noPermission();
        }

        $user = User::findOrFail($id);
        if (! $this->canManage($user)) {
            return $this->noPermission();
        }
        $roles = array_combine($this->assignableRoleNames(), $this->assignableRoleNames());
        $userRole = $user->roles->pluck('name','name')->all();

        return view('users.edit',compact('user','roles','userRole'));
    }

    /**
     * Update the specified resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function update(Request $request, $id)
    {
        if (! $this->actorIsSuperAdmin()) {
            return $this->noPermission();
        }

        $user = User::findOrFail($id);
        if (! $this->canManage($user)) {
            return $this->noPermission();
        }

        $this->validate($request, [
            'name' => 'required|string|max:255',
            'email' => 'required|email|unique:users,email,'.$user->id,
            'password' => 'nullable|min:8|same:confirm-password',
            'roles' => 'required|array',
            'roles.*' => ['string', Rule::in($this->assignableRoleNames())],
        ]);

        $input = array_merge($this->splitName((string) $request->input('name')), [
            'email' => $request->input('email'),
        ]);
        if ($request->filled('password')) {
            $input['password'] = Hash::make((string) $request->input('password'));
        }

        $user->update($input);
        $user->syncRoles($request->input('roles'));

        return redirect()->route('users.index')
                        ->with('success',trans('data_update_successfully'));
    }

    /**
     * Remove the specified resource from storage.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function destroy($id)
    {
        if (! $this->actorIsSuperAdmin()) {
            return $this->noPermission();
        }

        $user = User::findOrFail($id);
        if ($user->id === Auth::id() || ! $this->canManage($user)) {
            return $this->noPermission();
        }

        $user->delete();
        return redirect()->route('users.index')
                        ->with('success',trans('data_delete_successfully'));
    }

    private function actorIsSuperAdmin(): bool
    {
        return Auth::user()->hasRole(self::SUPER_ADMIN);
    }

    /** Only a Super Admin may change or delete a Super Admin account. */
    private function canManage(User $user): bool
    {
        return ! $user->hasRole(self::SUPER_ADMIN) || $this->actorIsSuperAdmin();
    }

    /** @return list<string> */
    private function assignableRoleNames(): array
    {
        return Role::query()
            ->when(! $this->actorIsSuperAdmin(), fn ($query) => $query->where('name', '!=', self::SUPER_ADMIN))
            ->orderBy('id')
            ->pluck('name')
            ->all();
    }

    /** @return array{first_name: string, last_name: string} */
    private function splitName(string $name): array
    {
        $parts = preg_split('/\s+/', trim($name), 2) ?: [''];

        return ['first_name' => $parts[0], 'last_name' => $parts[1] ?? ''];
    }

    private function noPermission()
    {
        return redirect(route('home'))->withErrors(['message' => trans('no_permission_message')]);
    }
}
