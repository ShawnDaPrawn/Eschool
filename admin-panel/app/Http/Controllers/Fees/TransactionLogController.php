<?php

declare(strict_types=1);

namespace App\Http\Controllers\Fees;

use App\Http\Controllers\Controller;
use App\Models\ClassSchool;
use App\Models\Mediums;
use App\Models\PaymentTransaction;
use App\Models\SessionYear;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

/**
 * Admin panel: the log of online fee payment transactions.
 */
class TransactionLogController extends Controller
{
    public function feesTransactionsLogsIndex(Request $request)
    {
        if (! Auth::user()->can('fees-paid')) {
            $response = [
                'message' => trans('no_permission_message'),
            ];

            return redirect(route('home'))->withErrors($response);
        }
        $session_year_all = SessionYear::select('id', 'name', 'default')->get();
        $classes = ClassSchool::orderByRaw('CONVERT(name, SIGNED) asc')->with('medium', 'sections', 'streams')->get();
        $mediums = Mediums::orderBy('id', 'ASC')->get();

        return response(view('fees.fees_transaction_logs', compact('classes', 'mediums', 'session_year_all')));
    }

    public function feesTransactionsLogsList()
    {
        if (! Auth::user()->can('fees-paid')) {
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

        // Fetching Students Data on Basis of Class Section ID with Realtion fees paid
        $sql = PaymentTransaction::with('student', 'session_year');

        if (isset($_GET['search']) && ! empty($_GET['search'])) {
            $search = $_GET['search'];
            $sql->where(fn ($query) => $query->where('id', 'LIKE', "%$search%")
                ->orwhere('order_id', 'LIKE', "%$search%")
                ->orwhere('payment_id', 'LIKE', "%$search%")
                ->orWhereHas('student.user', function ($q) use ($search) {
                    $q->where('first_name', 'LIKE', "%$search%")->orWhere('last_name', 'LIKE', "%$search%");
                }));
        }
        if (isset($_GET['session_year_id']) && ! empty($_GET['session_year_id'])) {
            $sql = $sql->where('session_year_id', $_GET['session_year_id']);
        }
        if (isset($_GET['class_id']) && ! empty($_GET['class_id'])) {
            $class_id = $_GET['class_id'];
            $sql = $sql->where('class_id', $class_id);
        }
        if (isset($_GET['payment_status']) && in_array($_GET['payment_status'], ['0', '1', '2'], true)) {
            $sql->where('payment_status', $_GET['payment_status']);
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
            $tempRow['id'] = $row->id;
            $tempRow['no'] = $no++;
            $tempRow['student_id'] = $row->student_id;
            $tempRow['student_name'] = $row->student->user->first_name.' '.$row->student->user->last_name;
            $tempRow['total_fees'] = $row->total_amount;
            $tempRow['payment_gateway'] = $row->payment_gateway;
            $tempRow['payment_status'] = $row->payment_status;
            $tempRow['order_id'] = $row->order_id;
            $tempRow['mode'] = $row->mode;
            $tempRow['cheque_no'] = $row->cheque_no;
            $tempRow['payment_id'] = $row->payment_id;
            $tempRow['payment_signature'] = $row->payment_signature;
            $tempRow['session_year_id'] = $row->session_year_id;
            $tempRow['session_year_name'] = $row->session_year->name;
            $tempRow['created_at'] = convertDateFormat($row->created_at, 'd-m-Y H:i:s');
            $tempRow['updated_at'] = convertDateFormat($row->updated_at, 'd-m-Y H:i:s');
            $rows[] = $tempRow;
        }
        $bulkData['rows'] = $rows;

        return response()->json($bulkData);
    }
}
