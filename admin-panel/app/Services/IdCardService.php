<?php

declare(strict_types=1);

namespace App\Services;

use App\Models\SessionYear;
use App\Models\Students;
use App\Models\User;
use Barryvdh\DomPDF\Facade\Pdf;
use Barryvdh\DomPDF\PDF as DomPdf;
use RuntimeException;

class IdCardService
{
    /** One PDF covers at most this many people (the largest page the lists offer). */
    public const MAX_CARDS = 500;

    /** mm -> points (dompdf paper units) */
    private const MM_TO_PT = 2.8346456693;

    /** mm -> px (template content scaling) */
    private const MM_TO_PX = 3.7795275591;

    /**
     * Build the student ID-card PDF for the given student ids, with the paper
     * sized to the configured card dimensions. Callers decide how to emit it
     * (->stream() for the web download, ->output() for an API/base64 payload).
     *
     * @param  array<int|string>  $studentIds
     *
     * @throws RuntimeException when the ID-card settings have not been configured.
     */
    public function buildPdf(array $studentIds): DomPdf
    {
        if (count($studentIds) > self::MAX_CARDS) {
            throw new RuntimeException(trans('Select at most :count people per ID card PDF.', ['count' => self::MAX_CARDS]));
        }
        $settings = getSettings();

        if (! isset($settings['student_id_card_fields'])) {
            throw new RuntimeException(trans('settings_not_found'));
        }

        $settings['student_id_card_fields'] = explode(',', $settings['student_id_card_fields']);

        $data = explode('storage/', $settings['signature'] ?? '');
        $settings['signature'] = end($data);

        $data = explode('storage/', $settings['background_image'] ?? '');
        $settings['background_image'] = end($data);

        $sessionYear = SessionYear::where('id', $settings['session_year'])->value('name');

        $width = $settings['card_width'] * self::MM_TO_PT;
        $height = $settings['card_height'] * self::MM_TO_PT;
        $customPaper = [0, 0, $width, $height];

        $students = Students::select('admission_no', 'roll_number', 'blood_group', 'user_id', 'class_section_id', 'guardian_id', 'father_id')
            ->with('user:id,first_name,last_name,gender,image,dob,permanent_address', 'class_section.class:id,name,medium_id,stream_id', 'class_section.class.medium:id,name', 'class_section.class.streams:id,name', 'father:id,first_name,last_name,mobile', 'guardian:id,first_name,last_name,mobile')
            ->whereIn('id', $studentIds)
            ->get();

        $settings['card_height'] = ($settings['card_height'] * self::MM_TO_PX).'px';

        return Pdf::loadView('students.id_card_template', compact('students', 'sessionYear', 'settings'))
            ->setPaper($customPaper);
    }

    /**
     * Build the staff/teacher ID-card PDF for the given user ids. Teachers and generic staff
     * are both User records, so this keys off user id and pulls the role (designation) and, for
     * teachers, the qualification. Uses the shared card settings + the `staff_id_card_fields` set.
     *
     * @param  array<int|string>  $userIds
     *
     * @throws RuntimeException when the ID-card settings have not been configured.
     */
    public function buildStaffPdf(array $userIds): DomPdf
    {
        if (count($userIds) > self::MAX_CARDS) {
            throw new RuntimeException(trans('Select at most :count people per ID card PDF.', ['count' => self::MAX_CARDS]));
        }
        $settings = getSettings();

        if (! isset($settings['staff_id_card_fields'])) {
            throw new RuntimeException(trans('settings_not_found'));
        }

        $settings['staff_id_card_fields'] = explode(',', $settings['staff_id_card_fields']);

        $data = explode('storage/', $settings['signature'] ?? '');
        $settings['signature'] = end($data);

        $data = explode('storage/', $settings['background_image'] ?? '');
        $settings['background_image'] = end($data);

        $width = $settings['card_width'] * self::MM_TO_PT;
        $height = $settings['card_height'] * self::MM_TO_PT;
        $customPaper = [0, 0, $width, $height];

        $staffs = User::select('id', 'first_name', 'last_name', 'gender', 'image', 'dob', 'email', 'mobile', 'current_address')
            ->with('roles:id,name', 'teacher:id,user_id,qualification')
            ->whereIn('id', $userIds)
            ->get();

        $settings['card_height'] = ($settings['card_height'] * self::MM_TO_PX).'px';

        return Pdf::loadView('staff.staff_id_card_template', compact('staffs', 'settings'))
            ->setPaper($customPaper);
    }
}
