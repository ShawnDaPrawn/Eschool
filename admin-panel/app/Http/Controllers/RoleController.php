<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Lang;
use Illuminate\Support\Str;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;

class RoleController extends Controller
{
    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
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
     * @return \Illuminate\Http\Response
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
     * @return \Illuminate\Http\Response
     */
    public function create()
    {
        $permissionGroups = $this->groupPermissions($this->loadAssignablePermissions());

        return view('roles.create', compact('permissionGroups'));
    }

    /**
     * Store a newly created resource in storage.
     *
     * @return \Illuminate\Http\Response
     */
    public function store(Request $request)
    {
        $request->validate([
            'name' => 'required|unique:roles,name',
            'permission' => 'required|array',
        ]);

        $role = Role::create(['name' => $request->input('name'), 'custom_role' => 1]);

        // Get permission IDs and sync them
        $permissionIds = $request->input('permission', []);
        if (! empty($permissionIds)) {
            $permissions = Permission::whereIn('id', $permissionIds)->get();
            $role->syncPermissions($permissions);
        }

        return redirect()->route('roles.index')->with('success', trans('data_store_successfully'));
    }

    /**
     * Display the specified resource.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
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
        $role = Role::find($id);
        $rolePermissions = Permission::join('role_has_permissions', 'role_has_permissions.permission_id', '=', 'permissions.id')
            ->where('role_has_permissions.role_id', $id)
            ->select('permissions.*')
            ->get();

        $permissionGroups = $this->groupPermissions($rolePermissions);

        return view('roles.show', compact('role', 'rolePermissions', 'permissionGroups'));
    }

    /**
     * Show the form for editing the specified resource.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function edit($id)
    {
        $role = Role::find($id);
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
     * @return \Illuminate\Http\Response
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
        $request->validate([
            'name' => 'required',
            'permission' => 'required|array',
        ]);
        try {
            $role = Role::find($id);
            $role->name = $request->input('name');
            $role->custom_role = 1;
            $role->save();

            // Get permission IDs and sync them
            $permissionIds = $request->input('permission', []);
            if (! empty($permissionIds)) {
                $permissions = Permission::whereIn('id', $permissionIds)->get();
                $role->syncPermissions($permissions);
            }
            $response = [
                'error' => false,
                'message' => trans('data_update_successfully'),
            ];
        } catch (\Throwable $e) {
            $response = [
                'error' => true,
                'message' => trans('error_occurred'),
                'data' => $e,
            ];
        }

        return redirect()->route('roles.index')->with('success', trans('data_update_successfully'));
    }

    /**
     * Remove the specified resource from storage.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
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
        try {
            $role = Role::find($id);
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

    private function loadAssignablePermissions(): Collection
    {
        // Only expose permissions granted to the Super Admin role so that custom
        // roles can't be assigned Teacher-only permissions (attendance, lessons,
        // assignments, online exam, etc.). This stays in sync automatically as the
        // Super Admin permission set changes, instead of a hand-kept exclusion list.
        return Permission::whereHas('roles', function ($query) {
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
