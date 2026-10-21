<?php

declare(strict_types=1);

namespace App\Http\Controllers\Fees;

use App\Http\Controllers\Controller;
use App\Models\FeesChoiceable;
use App\Models\FeesClass;
use App\Models\FeesType;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Validator;
use Throwable;

/**
 * Admin panel: the school's fee types (tuition, transport and so on).
 */
class TypeController extends Controller
{
    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function index()
    {
        if (! Auth::user()->can('fees-type')) {
            $response = [
                'message' => trans('no_permission_message'),
            ];

            return redirect(route('home'))->withErrors($response);
        }

        return view('fees.fees_types');
    }

    /**
     * Store a newly created resource in storage.
     *
     * @return \Illuminate\Http\Response
     */
    public function store(Request $request)
    {
        if (! Auth::user()->can('fees-type')) {
            $response = [
                'message' => trans('no_permission_message'),
            ];

            return redirect(route('home'))->withErrors($response);
        }
        $validator = Validator::make($request->all(), [
            'name' => 'required',
            'description' => 'nullable',
            // 'choiceable' => 'required|in:0,1'
        ]);
        if ($validator->fails()) {
            $response = [
                'error' => true,
                'message' => $validator->errors()->first(),
            ];

            return response()->json($response);
        }
        try {
            $fees_type = new FeesType;
            $fees_type->name = $request->name;
            $fees_type->description = $request->description;
            // $fees_type->choiceable = $request->choiceable;
            $fees_type->save();
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

    /**
     * Display the specified resource.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function show()
    {
        if (! Auth::user()->can('fees-type')) {
            $response = [
                'message' => trans('no_permission_message'),
            ];

            return redirect(route('home'))->withErrors($response);
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

        $sql = FeesType::select('*');
        if (isset($_GET['search']) && ! empty($_GET['search'])) {
            $search = $_GET['search'];
            $sql->where('id', 'LIKE', "%$search%")
                ->orwhere('name', 'LIKE', "%$search%")
                ->orwhere('description', 'LIKE', "%$search%")
                ->orwhere('created_at', 'LIKE', '%'.date('Y-m-d H:i:s', strtotime($search)).'%')
                ->orwhere('updated_at', 'LIKE', '%'.date('Y-m-d H:i:s', strtotime($search)).'%');
        }
        $total = $sql->count();

        $sql->orderBy($sort, $order)->skip($offset)->take($limit);
        $res = $sql->get();
        $bulkData = [];
        $bulkData['total'] = $total;
        $rows = [];
        $tempRow = [];
        $no = 1;

        // Batch load related data existence to avoid N+1 queries
        $feesTypeIds = $res->pluck('id')->toArray();
        $typesWithClasses = FeesClass::whereIn('fees_type_id', $feesTypeIds)->pluck('fees_type_id')->unique()->toArray();
        $typesWithChoiceables = FeesChoiceable::whereIn('fees_type_id', $feesTypeIds)->pluck('fees_type_id')->unique()->toArray();

        foreach ($res as $row) {
            $relatedData = [];
            if (in_array($row->id, $typesWithClasses)) {
                $relatedData[] = 'fee classes';
            }
            if (in_array($row->id, $typesWithChoiceables)) {
                $relatedData[] = 'fee choiceables';
            }

            $operate = '<a href="#" class="btn btn-xs btn-gradient-primary btn-rounded btn-icon edit-data" data-id='.$row->id.' title="'.trans('edit').'" data-toggle="modal" data-target="#editModal"><i class="fa fa-edit"></i></a>&nbsp;&nbsp;';
            $operate .= '
                <a
                    href="'.route('fees-type.destroy', $row->id).'"
                    class="btn btn-xs btn-gradient-danger btn-rounded btn-icon delete-form"
                    title="'.trans('delete').'"
                    data-id="'.$row->id.'"'
                .(! empty($relatedData) ? " data-related='".json_encode($relatedData)."'" : '').
                '>
                    <i class="fa fa-trash"></i>
                </a>';

            $tempRow['id'] = $row->id;
            $tempRow['no'] = $no++;
            $tempRow['name'] = $row->name;
            $tempRow['description'] = $row->description;
            // $tempRow['choiceable'] = $row->choiceable;
            $tempRow['created_at'] = convertDateFormat($row->created_at, 'd-m-Y H:i:s');
            $tempRow['updated_at'] = convertDateFormat($row->updated_at, 'd-m-Y H:i:s');
            $tempRow['operate'] = $operate;
            $rows[] = $tempRow;
        }

        $bulkData['rows'] = $rows;

        return response()->json($bulkData);
    }

    /**
     * Update the specified resource in storage.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function update(Request $request, $id)
    {
        if (! Auth::user()->can('fees-type')) {
            $response = [
                'message' => trans('no_permission_message'),
            ];

            return redirect(route('home'))->withErrors($response);
        }
        $validator = Validator::make($request->all(), [
            'edit_name' => 'required',
            'edit_description' => 'nullable',
        ]);
        if ($validator->fails()) {
            $response = [
                'error' => true,
                'message' => $validator->errors()->first(),
            ];

            return response()->json($response);
        }
        try {
            $fees_type = FeesType::findOrFail($id);
            $fees_type->name = $request->edit_name;
            $fees_type->description = $request->edit_description;
            $fees_type->save();
            $response = [
                'error' => false,
                'message' => trans('data_update_successfully'),
            ];
        } catch (Throwable $e) {
            $response = [
                'error' => true,
                'message' => trans('error_occurred'),
            ];
        }

        return response()->json($response);
    }

    /**
     * Remove the specified resource from storage.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function destroy($id)
    {
        if (! Auth::user()->can('fees-type')) {
            return response()->json([
                'error' => true,
                'message' => trans('no_permission_message'),
            ]);
        }

        try {
            $feesType = FeesType::findOrFail($id);

            // Cascade handled at model level
            $feesType->delete();

            return response()->json([
                'error' => false,
                'message' => trans('data_delete_successfully'),
            ]);
        } catch (\Throwable $e) {
            return response()->json([
                'error' => true,
                'message' => trans('error_occurred'),
            ]);
        }
    }
}
