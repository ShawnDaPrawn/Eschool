<?php

declare(strict_types=1);

namespace App\Http\Controllers\Student;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Services\Platform\Identity\AppTokens;
use App\Services\Platform\Identity\LearnerInvitations;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Throwable;

/**
 * Admin panel: reset a student's password.
 */
class PasswordController extends Controller
{
    public function reset_password()
    {
        if (! Auth::user()->can('student-reset-password')) {
            $response = [
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

        $sql = User::where('reset_request', 1);

        if (isset($_GET['search']) && ! empty($_GET['search'])) {
            $search = $_GET['search'];
            $sql->where(function ($query) use ($search) {
                $query->where('id', 'LIKE', "%$search%")
                    ->orWhere('email', 'LIKE', "%$search%")
                    ->orWhere('first_name', 'LIKE', "%$search%")
                    ->orWhere('last_name', 'LIKE', "%$search%")
                    ->orWhereRaw("concat(first_name, ' ', last_name) LIKE ?", ['%'.$search.'%']);
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
            $operate = '<button class="btn btn-xs btn-gradient-primary btn-action btn-rounded btn-icon reset_password" data-id='.$row->id.' title="Reset-Password"><i class="fa fa-edit"></i></button>&nbsp;&nbsp;';

            $tempRow['id'] = $row->id;
            $tempRow['no'] = $no++;
            $tempRow['name'] = $row->first_name.' '.$row->last_name;
            $tempRow['dob'] = $row->dob;
            $tempRow['email'] = $row->email;
            $tempRow['operate'] = $operate;
            $rows[] = $tempRow;
        }
        $bulkData['rows'] = $rows;

        return response()->json($bulkData);
    }

    /**
     * A learner asked for a new password (forgot-password in the app). Their
     * password becomes a random one nobody knows and the guardians are emailed
     * a link to choose a new one; the school can print a set-up code as well.
     * It used to go back to the date of birth.
     */
    public function change_password(Request $request, LearnerInvitations $invitations)
    {
        if (! Auth::user()->can('student-reset-password')) {
            $response = [
                'message' => trans('no_permission_message'),
            ];

            return response()->json($response);
        }
        try {
            // Students only: this used to take any user id and a date from the request,
            // so it could reset a staff or Super Admin password to a value the caller knew.
            $user = User::role('Student')->whereHas('student')->find($request->id);
            if (! $user) {
                return response()->json(['error' => true, 'message' => trans('no_permission_message')]);
            }
            $user->forceFill([
                'reset_request' => 0,
                'password' => Hash::make(LearnerInvitations::unguessablePassword()),
            ])->save();
            // Whoever had the old password is signed out of the app.
            AppTokens::revokeAll($user);
            $sent = $invitations->inviteGuardians($user, Auth::user());

            $response = [
                'error' => false,
                'message' => $sent > 0
                    ? trans('learner_invitation_sent', ['count' => $sent])
                    : trans('learner_invitation_no_guardian_email'),
                'data' => ['sheet_url' => route('students.sign-in-sheet', $user->id)],
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
}
