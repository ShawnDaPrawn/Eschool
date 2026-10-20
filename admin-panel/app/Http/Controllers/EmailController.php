<?php

namespace App\Http\Controllers;

use App\Models\EmailTempalate;
use Illuminate\Http\Request;

class EmailController extends Controller
{
    public function index()
    {
        $Emailtemplate = EmailTempalate::whereIn('type', ['student_registration', 'staff_registration', 'student_application_regect'])
            ->get()->keyBy('type');

        $template = $Emailtemplate->get('student_registration');
        $stafftemplate = $Emailtemplate->get('staff_registration');
        $rejectionTempplate = $Emailtemplate->get('student_application_regect');

        return view('settings.email-template', compact('template', 'stafftemplate', 'rejectionTempplate'));

    }

    public function store(Request $request)
    {
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

        EmailTempalate::updateOrCreate(
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
