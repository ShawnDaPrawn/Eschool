<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Models\Platform\Permission;
use App\Models\Platform\Role;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Lang;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;

class RoleController extends Controller
{
    // Retain old database grants for rollout history, not current role choices.
    private const RETIRED_FEE_PERMISSIONS = ['fees-type', 'fees-classes', 'fees-paid', 'fees-config'];

    /** Roles the application relies on; they are never edited or deleted here. */
    private const BUILT_IN_ROLES = ['Super Admin', 'Teacher', 'Parent', 'Student'];

    /**
     * Display a listing of the resource.
     *
     * @return Response
     */
    public function __construct()
    {
        $this->middleware('permission:role-list|role-create|role-edit|role-delete', ['only' => ['index', 'store']]);
        $this->middleware('permission:role-create', ['only' => ['create', 'store']]);
        $this->middleware('permission:role-edit', ['only' => ['edit', 'update']]);
        $this->middleware('permission:role-delete', ['only' => ['destroy']]);
    }

    /**
     * Display a listing of the resource.
     *
     * @return Response
     */
    public function index(Request $request)
    {
        $roles = Role::where('custom_role', 1)->orderBy('id', 'DESC')->paginate(5);

        return view('roles.index', compact('roles'))
            ->with('i', ($request->input('page', 1) - 1) * 5);
    }

    /**
     * Show the form for creating a new resource.
     *
     * @return Response
     */
    public function create()
    {
        $permissionGroups = $this->groupPermissions($this->loadAssignablePermissions());

        return view('roles.create', compact('permissionGroups'));
    }

    /**
     * Store a newly created resource in storage.
     *
     * @return Response
     */
    public function store(Request $request)
    {
        $request->validate([
            'name' => ['required', 'unique:roles,name', Rule::notIn(self::BUILT_IN_ROLES)],
            'permission' => 'required|array',
        ]);

        $role = Role::create(['name' => $request->input('name'), 'custom_role' => 1]);

        // Only permissions the create form offers can be granted.
        $role->syncPermissions($this->assignablePermissionsFrom($request->input('permission', [])));

        return redirect()->route('roles.index')->with('success', trans('data_store_successfully'));
    }

    /**
     * Display the specified resource.
     *
     * @param  int  $id
     * @return Response
     */
    public function show()
    {

        if (! Auth::user()->can('role-list')) {
            $response = [
                'error' => true,
                'message' => trans('no_permission_message'),
            ];

            return response()->json($response);
        }
        $offset = 0;
        $limit = 10;
        $sort = 'id';
        $order = 'DESC';

        if (isset($_GET['offset'])) {
            $offset = $_GET['offset'];
        }
        if (isset($_GET['limit'])) {
            $limit = $_GET['limit'];
        }

        if (isset($_GET['sort'])) {
            $sort = $_GET['sort'];
        }
        if (isset($_GET['order'])) {
            $order = $_GET['order'];
        }

        $sql = Role::where('custom_role', '!=', 0);

        if (isset($_GET['search']) && ! empty($_GET['search'])) {
            $search = $_GET['search'];
            $sql->where(function ($query) use ($search) {
                $query->where('id', 'LIKE', "%$search%")
                    ->orWhere('name', 'LIKE', "%$search%");
            });
        }
        $total = $sql->count();

        $sql->orderBy($sort, $order)->skip($offset)->take($limit);
        $res = $sql->get();

        $bulkData = [];
        $bulkData['total'] = $total;
        $rows = [];
        $tempRow = [];
        $no = 1;
        foreach ($res as $row) {
            $operate = '<a href='.route('roles-list', $row->id).' class="btn btn-xs btn-gradient-info btn-rounded btn-icon"><i class="fa fa-eye"></i></a>&nbsp;&nbsp;';
            $operate .= '<a href='.route('roles.edit', $row->id).' class="btn btn-xs btn-gradient-primary btn-rounded btn-icon" data-id='.$row->id.' title="Edit"><i class="fa fa-edit"></i></a>&nbsp;&nbsp;';
            $operate .= '<a href='.route('role-access.edit', $row->id).' class="btn btn-xs btn-gradient-success btn-rounded btn-icon" title="'.e(__('Access by module')).'" aria-label="'.e(__('Access by module')).'"><i class="fa fa-th-list"></i></a>&nbsp;&nbsp;';
            $operate .= '<a href='.route('roles.destroy', $row->id).' class="btn btn-xs btn-gradient-danger btn-rounded btn-icon delete-form" data-id='.$row->id.'><i class="fa fa-trash"></i></a>';

            $tempRow['id'] = $row->id;
            $tempRow['no'] = $no++;
            $tempRow['name'] = $row->name;
            $tempRow['operate'] = $operate;
            $tempRow['created_at'] = convertDateFormat($row->created_at, 'd-m-Y H:i:s');
            $tempRow['updated_at'] = convertDateFormat($row->updated_at, 'd-m-Y H:i:s');
            $rows[] = $tempRow;
        }

        $bulkData['rows'] = $rows;

        return response()->json($bulkData);
    }

    public function showList($id)
    {
        if (! Auth::user()->can('role-list')) {
            return redirect(route('home'))->withErrors(['message' => trans('no_permission_message')]);
        }
        // Another school's role is not found (Role is limited to the school).
        $role = Role::findOrFail($id);
        $rolePermissions = Permission::join('role_has_permissions', 'role_has_permissions.permission_id', '=', 'permissions.id')
            ->where('role_has_permissions.role_id', $id)
            ->whereNotIn('permissions.name', self::RETIRED_FEE_PERMISSIONS)
            ->select('permissions.*')
            ->get();

        $permissionGroups = $this->groupPermissions($rolePermissions);

        return view('roles.show', compact('role', 'rolePermissions', 'permissionGroups'));
    }

    /**
     * Show the form for editing the specified resource.
     *
     * @param  int  $id
     * @return Response
     */
    public function edit($id)
    {
        $role = Role::find($id);
        if (! $role || ! $this->isEditableRole($role)) {
            return redirect()->route('roles.index')->withErrors(['message' => trans('no_permission_message')]);
        }
        $permissionGroups = $this->groupPermissions($this->loadAssignablePermissions());
        $rolePermissions = DB::table('role_has_permissions')->where('role_has_permissions.role_id', $id)
            ->pluck('role_has_permissions.permission_id', 'role_has_permissions.permission_id')
            ->all();

        return view('roles.edit', compact('role', 'permissionGroups', 'rolePermissions'));
    }

    /**
     * Update the specified resource in storage.
     *
     * @param  int  $id
     * @return Response
     */
    public function update(Request $request, $id)
    {
        if (! Auth::user()->can('role-edit')) {
            $response = [
                'error' => true,
                'message' => trans('no_permission_message'),
            ];

            return response()->json($response);
        }
        $role = Role::find($id);
        if (! $role || ! $this->isEditableRole($role)) {
            return redirect()->route('roles.index')->withErrors(['message' => trans('no_permission_message')]);
        }
        $request->validate([
            'name' => ['required', 'unique:roles,name,'.$role->id, Rule::notIn(self::BUILT_IN_ROLES)],
            'permission' => 'required|array',
        ]);
        try {
            $role->name = $request->input('name');
            $role->save();

            // Only permissions the edit form offers can be granted.
            $role->syncPermissions($this->assignablePermissionsFrom($request->input('permission', [])));
        } catch (\Throwable $e) {
            report($e);

            return redirect()->route('roles.index')->withErrors(['message' => trans('error_occurred')]);
        }

        return redirect()->route('roles.index')->with('success', trans('data_update_successfully'));
    }

    /**
     * Remove the specified resource from storage.
     *
     * @param  int  $id
     * @return Response
     */
    public function destroy($id)
    {
        if (! Auth::user()->can('role-delete')) {
            $response = [
                'error' => true,
                'message' => trans('no_permission_message'),
            ];

            return response()->json($response);
        }
        $role = Role::find($id);
        if (! $role || ! $this->isEditableRole($role)) {
            return response()->json([
                'error' => true,
                'message' => trans('no_permission_message'),
            ]);
        }
        try {
            $role->delete();
            $response = [
                'error' => false,
                'message' => trans('data_delete_successfully'),
            ];
        } catch (\Throwable $e) {
            $response = [
                'error' => true,
                'message' => trans('error_occurred'),
            ];
        }

        return response()->json($response);
    }

    /** Only custom roles (created on the roles page) may be edited or deleted. */
    private function isEditableRole(Role $role): bool
    {
        return (int) $role->custom_role === 1 && ! in_array($role->name, self::BUILT_IN_ROLES, true);
    }

    /**
     * The requested permission ids, limited to the ones the create/edit forms offer.
     */
    private function assignablePermissionsFrom(mixed $permissionIds): Collection
    {
        $requested = array_map('intval', array_filter((array) $permissionIds, 'is_scalar'));

        return $this->loadAssignablePermissions()->whereIn('id', $requested)->values();
    }

    private function loadAssignablePermissions(): Collection
    {
        // Only expose permissions granted to the Super Admin role so that custom
        // roles can't be assigned Teacher-only permissions (attendance, lessons,
        // assignments, online exam, etc.). This stays in sync automatically as the
        // Super Admin permission set changes, instead of a hand-kept exclusion list.
        return Permission::whereNotIn('name', self::RETIRED_FEE_PERMISSIONS)->whereHas('roles', function ($query) {
            $query->where('name', 'Super Admin');
        })
            ->orderBy('id', 'ASC')
            ->get();
    }

    private function groupPermissions(Collection $permissions): array
    {
        $actionSuffixes = ['-list', '-create', '-edit', '-delete', '-approve'];
        $actionOrder = ['list', 'create', 'edit', 'delete', 'approve'];

        // Resources whose permissions don't follow the standard <resource>-<action>
        // suffix convention but should still be grouped together (matched by prefix).
        $prefixGroups = ['attendance', 'exam', 'fees'];

        // Permissions that don't match any auto-detection rule but belong to an
        // existing group. Maps permission name → [resource, action].
        $manualGroupings = [
            'class-attendance' => ['resource' => 'attendance', 'action' => 'class_attendance'],
            'exam-result' => ['resource' => 'exam',       'action' => 'exam_result'],
            'exam-timetable-create' => ['resource' => 'exam',     'action' => 'exam_timetable'],
            'generate-result' => ['resource' => 'exam',       'action' => 'generate_result'],
            'view-exam-result' => ['resource' => 'exam',       'action' => 'view_exam_result'],
            'class-timetable' => ['resource' => 'timetable',  'action' => 'class_timetable'],
            'teacher-timetable' => ['resource' => 'timetable',  'action' => 'teacher_timetable'],
        ];

        // Override the auto-derived group title for resources whose translation key
        // does not follow the standard underscore convention (resource → resource_key).
        $groupLabels = [
            'class-teacher' => __('assign_class_teacher'),
            'content' => __('web_settings_content'),
            'event' => __('events'),
            'faq' => __('faqs'),
            'form-field' => __('custom_fields'),
            'slider' => __('sliders'),
        ];

        $groups = [];
        $other = [];

        foreach ($permissions as $perm) {
            // Check manual groupings first before auto-detection.
            if (isset($manualGroupings[$perm->name])) {
                $resource = $manualGroupings[$perm->name]['resource'];
                $action = $manualGroupings[$perm->name]['action'];
            } else {
                $resource = null;
                $action = null;
            }

            if ($resource === null) {
                foreach ($actionSuffixes as $suffix) {
                    if (str_ends_with($perm->name, $suffix)) {
                        $resource = substr($perm->name, 0, -strlen($suffix));
                        $action = ltrim($suffix, '-');
                        break;
                    }
                }
            }

            // Fall back to prefix-based grouping for resources with non-standard actions.
            if ($resource === null) {
                foreach ($prefixGroups as $prefix) {
                    if (str_starts_with($perm->name, $prefix.'-')) {
                        $resource = $prefix;
                        $action = substr($perm->name, strlen($prefix) + 1);
                        break;
                    }
                }
            }

            if ($resource === null) {
                $otherKey = str_replace('-', '_', $perm->name);
                $other[] = [
                    'id' => $perm->id,
                    'name' => $perm->name,
                    'action_label' => Lang::has($otherKey)
                        ? __($otherKey)
                        : Str::headline(str_replace('-', ' ', $perm->name)),
                ];

                continue;
            }

            $translationKey = str_replace('-', '_', $resource);
            $groups[$resource]['title'] = $groupLabels[$resource]
                ?? (Lang::has($translationKey) ? __($translationKey) : Str::headline(str_replace('-', ' ', $resource)));
            $groups[$resource]['items'][] = [
                'id' => $perm->id,
                'name' => $perm->name,
                'action' => $action,
                'action_label' => in_array($action, $actionOrder, true) || Lang::has($action)
                    ? __($action)
                    : Str::headline(str_replace('-', ' ', $action)),
            ];
        }

        foreach ($groups as $key => &$group) {
            usort($group['items'], function ($a, $b) use ($actionOrder) {
                return array_search($a['action'], $actionOrder) <=> array_search($b['action'], $actionOrder);
            });
        }
        unset($group);

        ksort($groups);

        if (! empty($other)) {
            $groups['_other'] = [
                'title' => __('other'),
                'items' => $other,
            ];
        }

        return $groups;
    }
}
