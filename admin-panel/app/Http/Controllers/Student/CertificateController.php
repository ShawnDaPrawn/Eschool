<?php

declare(strict_types=1);

namespace App\Http\Controllers\Student;

use App\Http\Controllers\Controller;
use App\Models\SessionYear;
use App\Models\Students;
use Barryvdh\DomPDF\Facade\Pdf;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

/**
 * Admin panel: bonafide and leaving certificates.
 */
class CertificateController extends Controller
{
    public function bonafideCertificateIndex($id)
    {
        if (! Auth::user()->can('generate-document')) {
            $response = [
                'message' => trans('no_permission_message'),
            ];

            return redirect(route('home'))->withErrors($response);
        }
        $student = Students::where('id', $id)->first();

        return view('students.bonafide_certificate', compact('student'));
    }

    public function generateBonafideCertificate(Request $request)
    {

        if (! Auth::user()->can('generate-document')) {
            $response = [
                'message' => trans('no_permission_message'),
            ];

            return redirect(route('home'))->withErrors($response);
        }
        $reason = $request->reason;
        $valid_upto = $request->valid_upto;
        $id = $request->id;
        $date = date('d-m-Y', strtotime(Carbon::now()->toDateString()));

        $settings = getSettings();
        $sessionYear = SessionYear::select('name')->where('id', $settings['session_year'])->pluck('name')->first();
        $student = Students::select('roll_number', 'admission_no', 'user_id', 'class_section_id', 'guardian_id', 'father_id')->with('user:id,first_name,last_name,dob', 'class_section.class:id,name,medium_id,stream_id', 'class_section.class.medium:id,name', 'class_section.class.streams:id,name', 'father:id,first_name,last_name', 'guardian:id,first_name,last_name')->where('id', $id)->first();

        $student_name = $student->user->first_name.' '.$student->user->last_name;
        if ($student->father) {
            $guardian_name = $student->father->first_name.' '.$student->father->last_name;
        } else {
            $guardian_name = $student->guardian->first_name.' '.$student->guardian->last_name;
        }
        $gr_no = $student->admission_no;
        $dob = date('d-m-Y', strtotime($student->user->dob));
        $roll_number = $student->roll_number;
        $class_section = $student->class_section->class->name.' '.$student->class_section->section->name.' '.$student->class_section->class->medium->name.' '.($student->class_section->class->streams->name ?? '');

        $pdf = PDF::loadView('students.bonafide_template', compact('student_name', 'guardian_name', 'gr_no', 'dob', 'roll_number', 'class_section', 'sessionYear', 'settings', 'reason', 'valid_upto', 'date'));

        return $pdf->stream('bonafide_certificate.pdf');
    }

    public function leavingCertificateIndex($id)
    {
        if (! Auth::user()->can('generate-document')) {
            $response = [
                'message' => trans('no_permission_message'),
            ];

            return redirect(route('home'))->withErrors($response);
        }
        $student = Students::where('id', $id)->first();

        return view('students.leaving_certificate', compact('student'));
    }

    public function generateLeavingCertificate(Request $request)
    {

        if (! Auth::user()->can('generate-document')) {
            $response = [
                'message' => trans('no_permission_message'),
            ];

            return redirect(route('home'))->withErrors($response);
        }
        $reason = $request->reason;
        $promoted_to = $request->promoted_to;
        $general_conduct = $request->general_conduct;
        $remarks = $request->remark;
        $father_name = null;
        $mother_name = null;
        $guardian_name = null;

        $id = $request->id;
        $date = date('d-m-Y', strtotime(Carbon::now()->toDateString()));

        $settings = getSettings();
        $sessionYear = SessionYear::select('name')->where('id', $settings['session_year'])->pluck('name')->first();
        $student = Students::select('roll_number', 'admission_no', 'admission_date', 'user_id', 'class_section_id', 'guardian_id', 'father_id', 'mother_id')->with('user:id,first_name,last_name,dob', 'class_section.class:id,name,medium_id,stream_id', 'class_section.class.medium:id,name', 'class_section.class.streams:id,name', 'father:id,first_name,last_name', 'guardian:id,first_name,last_name')->where('id', $id)->first();

        $student_name = $student->user->first_name.' '.$student->user->last_name;

        if ($student->father) {
            $father_name = $student->father->first_name.' '.$student->father->last_name;
            $mother_name = $student->mother->first_name.' '.$student->mother->last_name;
        }

        if ($student->guardian) {
            $guardian_name = $student->guardian->first_name.' '.$student->guardian->last_name;
        }
        $admission_date = $student->admission_date;
        $gr_no = $student->admission_no;
        $dob = date('d-m-Y', strtotime($student->user->dob));
        $roll_number = $student->roll_number;
        $class_section = $student->class_section->class->name.' '.$student->class_section->section->name.' '.$student->class_section->class->medium->name.' '.($student->class_section->class->streams->name ?? '');

        $pdf = PDF::loadView('students.leaving_template', compact('student_name', 'guardian_name', 'gr_no', 'dob', 'roll_number', 'class_section', 'sessionYear', 'settings', 'reason', 'promoted_to', 'date', 'general_conduct', 'remarks', 'admission_date', 'father_name', 'mother_name'));

        return $pdf->stream('leaving_certificate.pdf');
    }
}
