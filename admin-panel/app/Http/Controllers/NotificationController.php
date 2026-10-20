<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use Throwable;
use Carbon\Carbon;
use App\Models\User;
use App\Models\Notification;
use Illuminate\Http\Request;
use App\Models\UserNotification;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Validator;

class NotificationController extends Controller
{
    public function index()
    {
        if (!Auth::user()->can('notification-list')) {
            $response = array(
                'message' => trans('no_permission_message')
            );
            return redirect(route('home'))->withErrors($response);
        }
        return view('notification.index');
    }

    public function getRecipients(Request $request)
    {
        if (!Auth::user()->can('notification-create')) {
            return response()->json([
                'error' => true,
                'message' => trans('no_permission_message'),
            ], 403);
        }

        $offset = (int) ($request->input('offset', 0));
        $limit = (int) ($request->input('limit', 10));
        $search = trim((string) $request->input('search', ''));
        $recipientType = (string) $request->input('recipient_type', '');

        $roleMap = [
            'students' => 'Student',
            'parents' => 'Parent',
            'teachers' => 'Teacher',
        ];

        if (!array_key_exists($recipientType, $roleMap)) {
            return response()->json(['total' => 0, 'rows' => []]);
        }

        $role = $roleMap[$recipientType];
        $sessionYearId = (int) getSettings('session_year')['session_year'];

        $query = User::query()
            ->whereNull('deleted_at')
            ->whereHas('roles', function ($q) use ($role) {
                $q->where('name', $role);
            });

        if ($recipientType === 'students') {
            $query->whereHas('student.studentSessions', function ($session) use ($sessionYearId) {
                $session->where('session_year_id', $sessionYearId);
            });
        } elseif ($recipientType === 'parents') {
            $query->whereHas('parent', function ($parent) use ($sessionYearId) {
                $parent->where(function ($child) use ($sessionYearId) {
                    $child->whereHas('fatherRelationChild.studentSessions', fn ($session) => $session->where('session_year_id', $sessionYearId))
                        ->orWhereHas('motherRelationChild.studentSessions', fn ($session) => $session->where('session_year_id', $sessionYearId))
                        ->orWhereHas('guardianRelationChild.studentSessions', fn ($session) => $session->where('session_year_id', $sessionYearId));
                });
            });
        }

        if ($search !== '') {
            $query->where(function ($q) use ($search) {
                $q->where('first_name', 'LIKE', "%{$search}%")
                    ->orWhere('last_name', 'LIKE', "%{$search}%")
                    ->orWhere('email', 'LIKE', "%{$search}%");
            });
        }

        $total = (clone $query)->count();

        $users = $query->orderBy('first_name', 'ASC')
            ->skip($offset)
            ->take($limit)
            ->get(['id', 'first_name', 'last_name', 'email', 'image']);

        $rows = [];
        $no = $offset + 1;
        foreach ($users as $user) {
            $rows[] = [
                'id' => $user->id,
                'no' => $no++,
                'first_name' => $user->first_name,
                'last_name' => $user->last_name,
                'email' => $user->email,
                'image' => $user->image,
            ];
        }

        return response()->json(['total' => $total, 'rows' => $rows]);
    }

    public function store(Request $request)
    {
        if (!Auth::user()->can('notification-create')) {
            $response = array(
                'message' => trans('no_permission_message')
            );
            return redirect(route('home'))->withErrors($response);
        }
        $validator = Validator::make($request->all(), [
            'send_to' => 'required|array|min:1',
            'send_to.*' => 'integer|in:1,3,4,5',
            'title' => 'required',
            'message' => 'required',
            'user_id' => 'array',
            'user_id.*' => 'integer',
        ]);
        if ($validator->fails()) {
            $response = array(
                'error' => true,
                'message' => $validator->errors()->first()
            );
            return response()->json($response);
        }
        try {
            $sendToGroups = array_map('intval', $request->input('send_to', []));
            $isBroadcast = in_array(1, $sendToGroups, true);
            $userIds = array_values(array_unique(array_filter(
                array_map('intval', $request->input('user_id', [])),
                static fn ($id) => $id > 0
            )));

            if (!$isBroadcast && empty($userIds)) {
                return response()->json([
                    'error' => true,
                    'message' => trans('please_select_at_least_one_user'),
                ]);
            }

            $type = "custom";
            $image = null;
            $userinfo = $isBroadcast ? "all" : "specific";

            $notification = new Notification();
            $notification->send_to = $isBroadcast ? 1 : 2;
            $notification->title = $request->title;
            $notification->session_year_id = (int) getSettings('session_year')['session_year'];
            $notification->message = $request->message;
            $notification->type = $type;
            $notification->date = Carbon::now();
            $notification->is_custom = 1;

            if ($request->hasFile('image')) {
                $notification_image = $request->file('image');
                // made file name with combination of current time
                $file_name = time() . '-' . $notification_image->getClientOriginalName();
                //made file path to store in database
                $file_path = 'notifications/' . $file_name;
                //resized image
                resizeImage($notification_image);
                //stored image to storage/public/parents folder
                $destinationPath = storage_path('app/public/notifications');
                $notification_image->move($destinationPath, $file_name);
                //saved file path to database
                $notification->image = $file_path;
                $image = asset('storage/' . $file_path);
            }

            $title = $request->title;
            $body =  $request->message;
            $type =  $type;
            $notification->save();

            // Only save individual user notifications for specific users
            if (!$isBroadcast) {
                foreach ($userIds as $data) {
                    $user_notification = new UserNotification();
                    $user_notification->notification_id = $notification->id;
                    $user_notification->user_id = $data;
                    $user_notification->save();
                }
            }

            // sendSimpleNotification($userIds, $title, $body, $type, $image, $userinfo);
            if ($isBroadcast) {
                sendNotificationToTopic($title, $body, $type, $image, $userinfo);
            }

            $response = array(
                'error' => false,
                'message' => trans('notification_sent_successfully')
            );
        } catch (Throwable $e) {
            $message = trans('error_occurred');
            $exceptionMessage = strtolower((string) $e->getMessage());
            if (str_contains($exceptionMessage, 'file') && str_contains($exceptionMessage, 'image')) {
                $message = trans('please_select_valid_image');
            } elseif (str_contains($exceptionMessage, 'permission') || str_contains($exceptionMessage, 'unauthorized')) {
                $message = trans('no_permission_message');
            }
            $response = array(
                'error' => true,
                'message' => $message,
                'data' => $e
            );
        }
        return response()->json($response);
    }

    public function show()
    {
        if (!Auth::user()->can('notification-list')) {
            $response = array(
                'error' => true,
                'message' => trans('no_permission_message')
            );
            return response()->json($response);
        }
        $offset = 0;
        $limit = 10;
        $sort = 'id';
        $order = 'DESC';

        if (isset($_GET['offset']))
            $offset = $_GET['offset'];
        if (isset($_GET['limit']))
            $limit = $_GET['limit'];

        if (isset($_GET['sort']))
            $sort = $_GET['sort'];
        if (isset($_GET['order']))
            $order = $_GET['order'];

        $session_year_id = $_GET['session_year_id'] ?? getSettings('session_year')['session_year'];

        $sql = Notification::where('id', '!=', 0)->where('is_custom', 1)->where('session_year_id', $session_year_id);
        if (isset($_GET['search']) && !empty($_GET['search'])) {
            $search = $_GET['search'];
            $sql->where('id', 'LIKE', "%$search%")
                ->orwhere('name', 'LIKE', "%$search%")
                ->orwhere('title', 'LIKE', "%$search%")
                ->orwhere('date', 'LIKE', "%$search%");
        }
        $total = $sql->count();

        $sql->orderBy($sort, $order)->skip($offset)->take($limit);
        $res = $sql->get();
        $bulkData = array();
        $bulkData['total'] = $total;
        $rows = array();
        $tempRow = array();
        $no = 1;

        foreach ($res as $row) {

            $operate = '<a href=' . route('notifications.destroy', $row->id) . ' class="btn btn-xs btn-gradient-danger btn-rounded btn-icon delete-form" data-id=' . $row->id . '><i class="fa fa-trash"></i></a>';

            $tempRow['id'] = $row->id;
            $tempRow['no'] = $no++;
            $tempRow['title'] = $row->title;
            $tempRow['type'] = $row->type;
            $tempRow['message'] = $row->message;
            $tempRow['image'] = $row->image;
            $tempRow['date'] = convertDateFormat($row->date, 'd-m-Y H:i:s');
            $tempRow['operate'] = $operate;
            $tempRow['created_at'] = convertDateFormat($row->created_at, 'd-m-Y H:i:s');
            $tempRow['updated_at'] = convertDateFormat($row->updated_at, 'd-m-Y H:i:s');
            $rows[] = $tempRow;
        }

        $bulkData['rows'] = $rows;
        return response()->json($bulkData);
    }

    public function destroy($id)
    {
        if (!Auth::user()->can('notification-delete')) {
            $response = array(
                'error' => true,
                'message' => trans('no_permission_message')
            );
            return response()->json($response);
        }
        try {
            $data = Notification::findOrFail($id);
            $data->delete();
            $response = [
                'error' => false,
                'message' => trans('data_delete_successfully')
            ];
        } catch (Throwable $e) {
            $response = array(
                'error' => true,
                'message' => trans('error_occurred'),
                'data' => $e
            );
        }
        return response()->json($response);
    }
}
