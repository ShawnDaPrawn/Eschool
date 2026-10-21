<?php

namespace App\Http\Controllers;

use App\Services\ResponseService;
use App\Models\EmailTemplate;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class EmailController extends Controller
{
    public function index()
    {
        if (! Auth::user()->can('email-setting-create')) {
            return redirect(route('home'))->withErrors(['message' => trans('no_permission_message')]);
        }

        $Emailtemplate = EmailTemplate::whereIn('type', ['student_registration', 'staff_registration', 'student_application_regect'])
            ->get()->keyBy('type');

        $template = $Emailtemplate->get('student_registration');
        $stafftemplate = $Emailtemplate->get('staff_registration');
        $rejectionTempplate = $Emailtemplate->get('student_application_regect');

        return view('settings.email-template', compact('template', 'stafftemplate', 'rejectionTempplate'));

    }

    public function store(Request $request)
    {
        if (! Auth::user()->can('email-setting-create')) {
            return response()->json(['error' => true, 'message' => trans('no_permission_message')], 403);
        }

        $request->validate([
            'type' => 'required|in:student,staff,rejection',
            'content' => 'required|string',
        ]);

        $map = [
            'student' => [
                'type' => 'student_registration',
                'subject' => 'Student Admission Confirmation',
            ],
            'staff' => [
                'type' => 'staff_registration',
                'subject' => 'Staff Registration Confirmation',
            ],
            'rejection' => [
                'type' => 'student_application_regect',
                'subject' => 'Staff Rejection ',
            ],
        ];

        $config = $map[$request->type];

        EmailTemplate::updateOrCreate(
            ['type' => $config['type']],
            [
                'subject' => $config['subject'],
                'content' => $request->content,
                'updated_at' => now(),
            ]
        );

        return response()->json([
            'message' => 'Email template saved successfully!',
        ]);
    }
}
