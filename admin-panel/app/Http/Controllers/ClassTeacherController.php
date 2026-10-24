<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Models\ClassSchool;
use App\Models\ClassSection;
use App\Models\ClassTeacher;
use App\Models\SessionYear;
use App\Models\Teacher;
use App\Models\Term;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Throwable;

class ClassTeacherController extends Controller
{
    public function teacher()
    {
        if (! Auth::user()->can('class-teacher-list')) {
            $response = [
                'message' => trans('no_permission_message'),
            ];

            return redirect(route('home'))->withErrors($response);
        }
        $class_section = ClassSection::with('class.medium', 'section', 'class.streams')->get();
        $session_years = SessionYear::orderBy('id', 'ASC')->get();

        $class_teacher_ids = ClassTeacher::whereNot('class_teacher_id', null)->pluck('class_teacher_id');
        // $assign_teacher_id = ClassSection::select('class_teacher_id')->whereNotNull('')->get()->pluck('class_teacher_id');
        // $teachers = Teacher::whereNotIn('id', $assign_teacher_id)->with('user')->get();
        $classes = ClassSchool::orderBy('id', 'DESC')->with('medium', 'streams')->get();

        return view('class.teacher', compact('class_section', 'classes', 'session_years'));
    }

    public function assign_teacher(Request $request)
    {
        if (! Auth::user()->can('class-teacher-edit')) {
            $response = [
                'error' => true,
                'message' => trans('no_permission_message'),
            ];

            return response()->json($response);
        }

        $request->validate([
            'class_section_id' => 'required|integer',
            'teacher_id' => 'required|integer',
            'session_year_id' => 'required|integer',
            'term_id' => 'nullable|integer',
        ]);

        $session_year_id = $request->session_year_id;

        try {
            $teacher = Teacher::findOrFail($request->teacher_id);
            $class_teacher = ClassTeacher::updateOrCreate(
                [
                    'class_section_id' => $request->class_section_id,
                    'class_teacher_id' => $request->teacher_id,
                    'session_year_id' => $session_year_id,
                    'term_id' => $request->term_id ?: null,
                ]
            );

            if ($class_teacher->wasRecentlyCreated) {
                $permission = [
                    'class-teacher',
                    'student-leave-approve',
                ];
                $teacher->user->givePermissionTo($permission);
            }

            $response = [
                'error' => false,
                'message' => trans('data_store_successfully'),
            ];
        } catch (Throwable $e) {
            report($e);
            $response = [
                'error' => true,
                'message' => trans('error_occurred'),
            ];
        }

        return response()->json($response);
    }

    public function show()
    {
        if (! Auth::user()->can('class-teacher-list')) {
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
            $offset = (int) $_GET['offset'];
        }

        if (isset($_GET['limit'])) {
            $limit = (int) $_GET['limit'];
        }

        if (isset($_GET['sort'])) {
            $sort = $_GET['sort'];
        }
        if (isset($_GET['order'])) {
            $order = $_GET['order'];
        }

        $session_year_id = $_GET['session_year_id'] ?? getSettings('session_year')['session_year'];

        $sql = ClassSection::with([
            'class.medium',
            'class.streams',
            'section',
            'classTeachers' => function ($q) use ($session_year_id) {
                $q->where('session_year_id', $session_year_id)
                    ->wherePivotNull('deleted_at');
            },
            'classTeachers.user',
        ]);

        if (isset($_GET['search']) && ! empty($_GET['search'])) {
            $search = $_GET['search'];
            $sql->where(function ($query) use ($search) {
                $query->where('id', 'LIKE', "%$search%")
                    ->orWhereHas('class.medium', function ($q) use ($search) {
                        $q->where('classes.name', 'LIKE', "%$search%")->orwhere('mediums.name', 'LIKE', "%$search%");
                    })
                    ->orWhereHas('section', function ($q) use ($search) {
                        $q->where('name', 'LIKE', "%$search%");
                    })
                    ->orWhereHas('classTeachers.user', function ($q) use ($search) {
                        $q->whereRaw("concat(users.first_name,' ',users.last_name) LIKE ?", ['%'.$search.'%'])->orwhere('users.first_name', 'LIKE', "%$search%")->orwhere('users.last_name', 'LIKE', "%$search%");
                    });
            });
        }
        if (! empty($_GET['class_id'])) {
            $sql = $sql->where('class_id', $_GET['class_id']);
        }

        // Base ordering only; the table's sortable columns (class, stream_name, ...) are derived
        // display values, not columns on class_sections, so the real sort is applied in PHP below.
        $res = $sql->orderBy('id', 'asc')->get();

        // Fetch terms for the selected session year
        $terms = Term::where('session_year_id', $session_year_id)->orderBy('id', 'ASC')->get();
        $filter_term_id = $_GET['term_id'] ?? null;

        $bulkData = [];
        $rows = [];
        $no = 1;
        foreach ($res as $row) {
            $operate = '<a class="btn btn-xs btn-gradient-primary btn-rounded btn-icon editdata" data-id='.$row->id.' title="Edit" data-toggle="modal" data-target="#editModal"><i class="fa fa-edit"></i></a>&nbsp;&nbsp;';

            $includesTerms = $row->class->getIncludeTermsForSession($session_year_id);

            if ($includesTerms && $terms->isNotEmpty()) {
                // Filter to specific term if selected, otherwise show all
                $termsToShow = $filter_term_id
                    ? $terms->where('id', $filter_term_id)
                    : $terms;

                foreach ($termsToShow as $term) {
                    // Filter teachers for this specific term
                    $termTeachers = $row->classTeachers->filter(function ($teacher) use ($term) {
                        return $teacher->pivot->term_id == $term->id;
                    });

                    $class_teacher_ids = [];
                    $class_teacher_name = [];
                    foreach ($termTeachers as $class_teacher) {
                        $class_teacher_ids[] = $class_teacher->pivot->class_teacher_id;
                        $class_teacher_name[] = $class_teacher->user->first_name.' '.$class_teacher->user->last_name ?? '-';
                    }

                    $tempRow = [];
                    $tempRow['id'] = $row->id;
                    $tempRow['class_id'] = $row->class_id;
                    $tempRow['section_id'] = $row->section_id;
                    $tempRow['no'] = $no++;
                    $tempRow['class'] = $row->class->name.' - '.$row->class->medium->name;
                    $tempRow['stream_name'] = $row->class->streams->name ?? '-';
                    $tempRow['section'] = $row->section->name;
                    $tempRow['term_id'] = $term->id;
                    $tempRow['term_name'] = $term->name;
                    $tempRow['teacher_id'] = $class_teacher_ids ?? '-';
                    $tempRow['teachers'] = $class_teacher_name ?? '-';
                    $tempRow['operate'] = $operate;
                    $rows[] = $tempRow;
                }
            } else {
                // No terms — skip if a specific term filter is applied
                if ($filter_term_id) {
                    continue;
                }

                // Teachers without term (null term_id)
                $class_teacher_ids = [];
                $class_teacher_name = [];
                foreach ($row->classTeachers as $class_teacher) {
                    $class_teacher_ids[] = $class_teacher->pivot->class_teacher_id;
                    $class_teacher_name[] = $class_teacher->user->first_name.' '.$class_teacher->user->last_name ?? '-';
                }

                $tempRow = [];
                $tempRow['id'] = $row->id;
                $tempRow['class_id'] = $row->class_id;
                $tempRow['section_id'] = $row->section_id;
                $tempRow['no'] = $no++;
                $tempRow['class'] = $row->class->name.' - '.$row->class->medium->name;
                $tempRow['stream_name'] = $row->class->streams->name ?? '-';
                $tempRow['section'] = $row->section->name;
                $tempRow['term_id'] = null;
                $tempRow['term_name'] = '-';
                $tempRow['teacher_id'] = $class_teacher_ids ?? '-';
                $tempRow['teachers'] = $class_teacher_name ?? '-';
                $tempRow['operate'] = $operate;
                $rows[] = $tempRow;
            }
        }

        $total = count($rows);

        // Sort the assembled (term-expanded) rows by the requested column. These are derived
        // display values (class, stream_name, section, ...), not real columns on class_sections,
        // so they can't be ordered via the query — sort them here in PHP before paginating.
        $sortableFields = ['id', 'class', 'stream_name', 'section', 'term_name'];
        $sortKey = in_array($sort, $sortableFields, true) ? $sort : 'id';
        $direction = strtolower((string) $order) === 'asc' ? 1 : -1;
        usort($rows, function ($a, $b) use ($sortKey, $direction) {
            $valueA = $a[$sortKey] ?? '';
            $valueB = $b[$sortKey] ?? '';
            if (is_numeric($valueA) && is_numeric($valueB)) {
                return ($valueA <=> $valueB) * $direction;
            }

            return strnatcasecmp((string) $valueA, (string) $valueB) * $direction;
        });

        // Apply pagination AFTER generating term rows
        $rows = array_slice($rows, $offset, $limit);

        // Renumber the visible rows so the "no." column stays sequential after sorting/paging.
        foreach ($rows as $index => $rowData) {
            $rows[$index]['no'] = $offset + $index + 1;
        }

        $bulkData['total'] = $total;
        $bulkData['rows'] = array_values($rows);

        return response()->json($bulkData);
    }

    public function removeClassTeacher($id, $class_teacher_id, Request $request)
    {
        if (! Auth::user()->can('class-teacher-delete')) {
            $response = [
                'error' => true,
                'message' => trans('no_permission_message'),
            ];

            return response()->json($response);
        }
        try {
            $term_id = $request->input('term_id');

            $query = ClassTeacher::where('class_section_id', $id)
                ->where('class_teacher_id', $class_teacher_id);

            if (! is_null($term_id)) {
                $query->where('term_id', $term_id);
            }

            $class_teacher = $query->first();

            if (! $class_teacher) {
                return response()->json([
                    'error' => true,
                    'message' => 'Class teacher not found',
                ]);
            }

            $permission = [
                'class-teacher',
                'student-leave-approve',
            ];

            $old_teacher = Teacher::with('user')->find($class_teacher_id);

            if ($old_teacher && $old_teacher->user) {
                foreach ($permission as $perm) {
                    if ($old_teacher->user->hasPermissionTo($perm)) {
                        $old_teacher->user->revokePermissionTo($perm);
                    }
                }
            }

            $class_teacher->delete();

            return response()->json([
                'error' => false,
                'message' => trans('data_delete_successfully'),
            ]);
        } catch (Throwable $e) {
            report($e);

            return response()->json([
                'error' => true,
                'message' => trans('error_occurred'),
            ]);
        }
    }

    public function getClassTeacherlist($class_section_id, $session_year_id)
    {
        if (! Auth::user()->can('class-teacher-list')) {
            return response()->json(['error' => true, 'message' => trans('no_permission_message')]);
        }
        $term_id = request('term_id');
        $response = Teacher::with('user')->orWhereHas('classTeachers', function ($q) use ($class_section_id, $session_year_id, $term_id) {
            $q->where('class_section_id', $class_section_id);
            $q->where('session_year_id', $session_year_id);
            if ($term_id) {
                $q->where('term_id', $term_id);
            } else {
                $q->whereNull('term_id');
            }
        })->get();

        return response()->json($response);
    }

    public function getNotClassTeacherList($class_section_id, $session_year_id)
    {
        if (! Auth::user()->can('class-teacher-list')) {
            return response()->json(['error' => true, 'message' => trans('no_permission_message')]);
        }
        $term_id = request('term_id');
        $response = Teacher::with('user')->whereDoesntHave('classTeachers', function ($q) use ($class_section_id, $session_year_id, $term_id) {
            $q->where('class_section_id', $class_section_id);
            $q->where('session_year_id', $session_year_id);
            if ($term_id) {
                $q->where('term_id', $term_id);
            } else {
                $q->whereNull('term_id');
            }
        })->get();

        return response()->json($response);
    }
}
