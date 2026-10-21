<?php

declare(strict_types=1);

namespace App\Http\Controllers\Fees;

use App\Http\Controllers\Controller;
use App\Models\ClassSchool;
use App\Models\FeesClass;
use App\Models\FeesType;
use App\Models\Mediums;
use App\Models\SessionYear;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Validator;

/**
 * Admin panel: which fee types each class is charged, and how much.
 */
class ClassController extends Controller
{
    public function feesClassListIndex()
    {
        if (! Auth::user()->can('fees-classes')) {
            $response = [
                'message' => trans('no_permission_message'),
            ];

            return redirect(route('home'))->withErrors($response);
        }
        $classes = ClassSchool::orderByRaw('CONVERT(name, SIGNED) asc')->with('medium', 'sections', 'streams')->get();
        $fees_type = FeesType::orderBy('id', 'ASC')->pluck('name', 'id');
        $fees_type_data = FeesType::get();
        $mediums = Mediums::orderBy('id', 'ASC')->get();
      $session_years = SessionYear::orderBy('id', 'ASC')->get();
        
        

        return response(view('fees.fees_class', compact('classes', 'fees_type', 'fees_type_data', 'mediums', 'session_years')));
    }

    public function feesClassList()
    {
        if (! Auth::user()->can('fees-classes')) {
            $response = [
                'message' => trans('no_permission_message'),
            ];

            return redirect(route('home'))->withErrors($response);
        }
        $offset = 0;
        $limit = 10;
        $sort = 'id';
        $session_year_id = $_GET['session_year_id'] ?? getSettings('session_year')['session_year'];
        if (isset($_GET['offset'])) {
            $offset = $_GET['offset'];
        }
        if (isset($_GET['limit'])) {
            $limit = $_GET['limit'];
        }

        if (isset($_GET['sort'])) {
            $sort = $_GET['sort'];
        }

        $sql = ClassSchool::orderByRaw('CONVERT(name, SIGNED) asc')
            ->with([
                'fees_class' => function ($query) use ($session_year_id) {
                    $query->where('session_year_id', $session_year_id);
                },
                'medium',
                'streams',
            ]);

        if (isset($_GET['search']) && ! empty($_GET['search'])) {
            $search = $_GET['search'];
            $sql->where('id', 'LIKE', "%$search%")
                ->orwhere('name', 'LIKE', "%$search%");
        }
        if (isset($_GET['medium_id']) && ! empty($_GET['medium_id'])) {
            $sql = $sql->where('medium_id', $_GET['medium_id']);
        }
        $total = $sql->count();

        $sql->skip($offset)->take($limit);
        $res = $sql->get();
        $bulkData = [];
        $bulkData['total'] = $total;
        $rows = [];
        $tempRow = [];
        $no = 1;

        foreach ($res as $row) {

            $row = (object) $row;
            $operate = '<a href="#" class="btn btn-xs btn-gradient-primary btn-rounded btn-icon edit-data" data-id='.$row->id.' title="'.trans('edit').'" data-toggle="modal" data-target="#editModal"><i class="fa fa-edit"></i></a>&nbsp;&nbsp;';

            $tempRow['no'] = $no++;
            $tempRow['class_id'] = $row->id;
            $tempRow['class_name'] = $row->name.' '.$row->medium->name;
            $tempRow['stream_name'] = $row->streams->name ?? '-';
            if (count($row->fees_class)) {
                $total_amount = 0;
                $base_amount = 0;
                $fees_type_table = [];
                foreach ($row->fees_class as $fees_details) {
                    $fees_type_table[] = [
                        'id' => $fees_details->id,
                        'fees_name' => $fees_details->fees_type->name,
                        'amount' => $fees_details->amount,
                        'choiceable' => $fees_details->choiceable,
                        'fees_type_id' => $fees_details->fees_type->id,
                    ];
                    if ($fees_details->choiceable == 0) {
                        $base_amount += $fees_details->amount;
                    }
                    $total_amount += $fees_details->amount;
                }
                $tempRow['fees_type'] = isset($fees_type_table) ? $fees_type_table : ' ';
                $tempRow['base_amount'] = round($base_amount, 2);
                $tempRow['total_amount'] = round($total_amount, 2);
            } else {
                $tempRow['fees_type'] = [];
                $tempRow['base_amount'] = '-';
                $tempRow['total_amount'] = '-';
            }
            $tempRow['created_at'] = convertDateFormat($row->created_at, 'd-m-Y H:i:s');
            $tempRow['updated_at'] = convertDateFormat($row->updated_at, 'd-m-Y H:i:s');
            $tempRow['operate'] = $operate;
            $rows[] = $tempRow;
        }

        $bulkData['rows'] = $rows;

        return response()->json($bulkData);
    }

    public function updateFeesClass(Request $request)
    {
        if (! Auth::user()->can('fees-classes')) {
            $response = [
                'message' => trans('no_permission_message'),
            ];

            return redirect(route('home'))->withErrors($response);
        }
        $validation_rules = [
            'class_id' => 'required|numeric',
            'edit_fees_type.*.fees_type_id' => 'required',
            'edit_fees_type.*.amount' => 'required:edit_fees_type',
            'edit_fees_type.*.choiceable' => 'required|in:0,1',
            'session_year_id' => 'required',
        ];
        $validator = Validator::make($request->all(), $validation_rules);

        if ($validator->fails()) {
            $response = [
                'error' => true,
                'message' => $validator->errors()->first(),
            ];

            return response()->json($response);
        }
        try {
            // //Update Fees Type For Class first
            if ($request->edit_fees_type) {
                foreach ($request->edit_fees_type as $row) {
                    $edit_fees_type = FeesClass::findOrFail($row['fees_class_id']);
                    $edit_fees_type->fees_type_id = $row['fees_type_id'];
                    $edit_fees_type->amount = $row['amount'];
                    $edit_fees_type->choiceable = $row['choiceable'];
                    $edit_fees_type->update();
                }
            }

            // Add New Fees Type For Class
            if ($request->fees_type) {
                $fees_type = [];
                foreach ($request->fees_type as $row) {
                    $fees_type[] = [
                        'class_id' => $request->class_id,
                        'session_year_id' => $request->session_year_id,
                        'fees_type_id' => $row['fees_type_id'],
                        'choiceable' => $row['choiceable'],
                        'amount' => $row['amount'],
                    ];
                }
                FeesClass::insert($fees_type);
            }
            $response = [
                'error' => false,
                'message' => trans('data_store_successfully'),
            ];
        } catch (\Throwable $e) {
            report($e);
            $response = [
                'error' => true,
                'message' => trans('error_occurred'),
            ];
        }

        return response()->json($response);
    }

    public function removeFeesClass($id)
    {
        if (! Auth::user()->can('fees-classes')) {
            return response()->json([
                'error' => true,
                'message' => trans('no_permission_message'),
            ]);
        }

        try {
            $feesClass = FeesClass::findOrFail($id);

            // Cascade handled at model level
            $feesClass->delete();

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
