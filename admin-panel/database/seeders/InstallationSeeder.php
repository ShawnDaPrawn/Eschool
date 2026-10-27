<?php

namespace Database\Seeders;

use App\Models\Language;
use App\Models\Platform\Permission;
use App\Models\Platform\School;
use App\Models\Settings;
use App\Services\Platform\Tenancy\CurrentSchool;
use App\Services\Platform\Tenancy\SchoolRoles;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Artisan;

class InstallationSeeder extends Seeder
{
    /** In the school already chosen, or else the default school (a single-school install). */
    public function run(): void
    {
        app(CurrentSchool::class)->runInSchoolOrDefault(fn () => $this->seedSchool());
    }

    /**
     * Run the database seeds.
     */
    private function seedSchool(): void
    {

        // Add Permissions
        $permissions = [
            ['name' => 'fee-accounts-manage'], ['name' => 'fee-billing-manage'], ['name' => 'fee-payments-manage'], ['name' => 'fee-credits-manage'], ['name' => 'fee-items-manage'],
            ['name' => 'fee-plans-manage'],
            ['name' => 'pathways-manage'],            ['name' => 'family-visits-manage'], ['name' => 'family-forms-manage'], ['name' => 'family-health-read'], ['name' => 'family-health-manage'],
            ['name' => 'ai-settings-manage'],
            ['name' => 'ai-flags-review'],
            ['name' => 'cards-manage'], ['name' => 'cards-readers-manage'], ['name' => 'cards-encode'],
            ['name' => 'pastoral-sensitive-access'], ['name' => 'activity-groups-manage'], ['name' => 'wellbeing-configure'],
            ['name' => 'marks-view-all'], ['name' => 'marks-release'],
            ['name' => 'academic-structure-manage'], ['name' => 'communication-manage'], ['name' => 'consultations-manage'],
            ['name' => 'curriculum-read'], ['name' => 'curriculum-manage'], ['name' => 'curriculum-publish'],
            ['id' => 1, 'name' => 'role-list'],
            ['id' => 2, 'name' => 'role-create'],
            ['id' => 3, 'name' => 'role-edit'],
            ['id' => 4, 'name' => 'role-delete'],

            ['id' => 5, 'name' => 'medium-list'],
            ['id' => 6, 'name' => 'medium-create'],
            ['id' => 7, 'name' => 'medium-edit'],
            ['id' => 8, 'name' => 'medium-delete'],

            ['id' => 9, 'name' => 'section-list'],
            ['id' => 10, 'name' => 'section-create'],
            ['id' => 11, 'name' => 'section-edit'],
            ['id' => 12, 'name' => 'section-delete'],

            ['id' => 13, 'name' => 'class-list'],
            ['id' => 14, 'name' => 'class-create'],
            ['id' => 15, 'name' => 'class-edit'],
            ['id' => 16, 'name' => 'class-delete'],

            ['id' => 17, 'name' => 'subject-list'],
            ['id' => 18, 'name' => 'subject-create'],
            ['id' => 19, 'name' => 'subject-edit'],
            ['id' => 20, 'name' => 'subject-delete'],

            ['id' => 21, 'name' => 'teacher-list'],
            ['id' => 22, 'name' => 'teacher-create'],
            ['id' => 23, 'name' => 'teacher-edit'],
            ['id' => 24, 'name' => 'teacher-delete'],

            ['id' => 25, 'name' => 'class-teacher-list'],
            ['id' => 26, 'name' => 'class-teacher-create'],
            ['id' => 27, 'name' => 'class-teacher-edit'],
            ['id' => 28, 'name' => 'class-teacher-delete'],

            ['id' => 29, 'name' => 'parents-list'],
            ['id' => 31, 'name' => 'parents-edit'],

            ['id' => 33, 'name' => 'session-year-list'],
            ['id' => 34, 'name' => 'session-year-create'],
            ['id' => 35, 'name' => 'session-year-edit'],
            ['id' => 36, 'name' => 'session-year-delete'],

            ['id' => 37, 'name' => 'student-list'],
            ['id' => 38, 'name' => 'student-create'],
            ['id' => 39, 'name' => 'student-edit'],
            ['id' => 40, 'name' => 'student-delete'],

            ['id' => 41, 'name' => 'category-list'],
            ['id' => 42, 'name' => 'category-create'],
            ['id' => 43, 'name' => 'category-edit'],
            ['id' => 44, 'name' => 'category-delete'],

            ['id' => 45, 'name' => 'subject-teacher-list'],
            ['id' => 46, 'name' => 'subject-teacher-create'],
            ['id' => 47, 'name' => 'subject-teacher-edit'],
            ['id' => 48, 'name' => 'subject-teacher-delete'],

            ['id' => 49, 'name' => 'timetable-list'],
            ['id' => 50, 'name' => 'timetable-create'],
            ['id' => 51, 'name' => 'timetable-edit'],
            ['id' => 52, 'name' => 'timetable-delete'],

            ['id' => 53, 'name' => 'attendance-list'],
            ['id' => 54, 'name' => 'attendance-create'],
            ['id' => 55, 'name' => 'attendance-edit'],
            ['id' => 56, 'name' => 'attendance-delete'],

            ['id' => 57, 'name' => 'holiday-list'],
            ['id' => 58, 'name' => 'holiday-create'],
            ['id' => 59, 'name' => 'holiday-edit'],
            ['id' => 60, 'name' => 'holiday-delete'],

            ['id' => 61, 'name' => 'announcement-list'],
            ['id' => 62, 'name' => 'announcement-create'],
            ['id' => 63, 'name' => 'announcement-edit'],
            ['id' => 64, 'name' => 'announcement-delete'],

            ['id' => 65, 'name' => 'slider-list'],
            ['id' => 66, 'name' => 'slider-create'],
            ['id' => 67, 'name' => 'slider-edit'],
            ['id' => 68, 'name' => 'slider-delete'],

            ['id' => 69, 'name' => 'class-timetable'],
            ['id' => 70, 'name' => 'teacher-timetable'],
            ['id' => 73, 'name' => 'class-attendance'],

            ['id' => 74, 'name' => 'exam-create'],
            ['id' => 75, 'name' => 'exam-list'],
            ['id' => 76, 'name' => 'exam-edit'],
            ['id' => 77, 'name' => 'exam-delete'],
            ['id' => 78, 'name' => 'exam-upload-marks'],

            ['id' => 79, 'name' => 'setting-create'],
            ['id' => 80, 'name' => 'fcm-setting-create'],

            ['id' => 81, 'name' => 'assignment-create'],
            ['id' => 82, 'name' => 'assignment-list'],
            ['id' => 83, 'name' => 'assignment-edit'],
            ['id' => 84, 'name' => 'assignment-delete'],
            ['id' => 85, 'name' => 'assignment-submission'],

            ['id' => 86, 'name' => 'email-setting-create'],
            ['id' => 87, 'name' => 'privacy-policy'],
            ['id' => 88, 'name' => 'contact-us'],
            ['id' => 89, 'name' => 'about-us'],

            ['id' => 90, 'name' => 'student-reset-password'],

            ['id' => 93, 'name' => 'promote-student-list'],
            ['id' => 95, 'name' => 'promote-student-edit'],

            ['id' => 97, 'name' => 'language-list'],
            ['id' => 98, 'name' => 'language-create'],
            ['id' => 99, 'name' => 'language-edit'],
            ['id' => 100, 'name' => 'language-delete'],

            ['id' => 101, 'name' => 'lesson-list'],
            ['id' => 102, 'name' => 'lesson-create'],
            ['id' => 103, 'name' => 'lesson-edit'],
            ['id' => 104, 'name' => 'lesson-delete'],

            ['id' => 105, 'name' => 'topic-list'],
            ['id' => 106, 'name' => 'topic-create'],
            ['id' => 107, 'name' => 'topic-edit'],
            ['id' => 108, 'name' => 'topic-delete'],

            ['id' => 109, 'name' => 'class-teacher'],
            ['id' => 110, 'name' => 'terms-condition'],

            ['id' => 111, 'name' => 'assign-class-to-new-student'],
            ['id' => 112, 'name' => 'exam-timetable-create'],
            ['id' => 113, 'name' => 'grade-create'],
            ['id' => 114, 'name' => 'update-admin-profile'],
            ['id' => 115, 'name' => 'exam-result'],

            ['id' => 116, 'name' => 'fees-type'],
            ['id' => 117, 'name' => 'fees-classes'],
            ['id' => 118, 'name' => 'fees-paid'],
            ['id' => 119, 'name' => 'fees-config'],

            ['id' => 120, 'name' => 'manage-online-exam'],

            ['id' => 121, 'name' => 'stream-list'],
            ['id' => 122, 'name' => 'stream-create'],
            ['id' => 123, 'name' => 'stream-edit'],
            ['id' => 124, 'name' => 'stream-delete'],

            ['id' => 125, 'name' => 'shift-list'],
            ['id' => 126, 'name' => 'shift-create'],
            ['id' => 127, 'name' => 'shift-edit'],
            ['id' => 128, 'name' => 'shift-delete'],

            ['name' => 'profile-person-view'],
            ['name' => 'profile-person-edit'],
            ['name' => 'profile-sensitive-view'],
            ['name' => 'profile-sensitive-export'],
            ['id' => 129, 'name' => 'form-field-list'],
            ['id' => 130, 'name' => 'form-field-create'],
            ['id' => 131, 'name' => 'form-field-edit'],
            ['id' => 132, 'name' => 'form-field-delete'],

            ['id' => 133, 'name' => 'notification-list'],
            ['id' => 134, 'name' => 'notification-create'],
            ['id' => 136, 'name' => 'notification-delete'],

            ['id' => 137, 'name' => 'event-create'],
            ['id' => 138, 'name' => 'event-list'],
            ['id' => 140, 'name' => 'event-delete'],
            ['id' => 141, 'name' => 'event-edit'],

            ['id' => 142, 'name' => 'program-create'],
            ['id' => 143, 'name' => 'program-list'],
            ['id' => 144, 'name' => 'program-delete'],
            ['id' => 145, 'name' => 'program-edit'],

            ['id' => 146, 'name' => 'media-create'],
            ['id' => 147, 'name' => 'media-list'],
            ['id' => 148, 'name' => 'media-delete'],
            ['id' => 149, 'name' => 'media-edit'],

            ['id' => 150, 'name' => 'faq-create'],
            ['id' => 151, 'name' => 'faq-list'],
            ['id' => 152, 'name' => 'faq-delete'],
            ['id' => 153, 'name' => 'faq-edit'],

            ['id' => 154, 'name' => 'content-create'],
            ['id' => 155, 'name' => 'content-list'],
            ['id' => 156, 'name' => 'content-edit'],

            ['id' => 157, 'name' => 'staff-create'],
            ['id' => 158, 'name' => 'staff-list'],
            ['id' => 159, 'name' => 'staff-delete'],
            ['id' => 160, 'name' => 'staff-edit'],

            ['id' => 161, 'name' => 'generate-id-card'],
            ['id' => 162, 'name' => 'generate-document'],
            ['id' => 163, 'name' => 'generate-result'],

            ['id' => 164, 'name' => 'leave-setting-create'],
            ['id' => 165, 'name' => 'leave-create'],
            ['id' => 166, 'name' => 'leave-delete'],
            ['id' => 167, 'name' => 'leave-edit'],
            ['id' => 168, 'name' => 'leave-list'],
            ['id' => 169, 'name' => 'leave-approve'],

            ['id' => 170, 'name' => 'chat-settings'],
            ['id' => 172, 'name' => 'attendance-report'],

            ['id' => 173, 'name' => 'term-list'],
            ['id' => 174, 'name' => 'term-create'],
            ['id' => 175, 'name' => 'term-edit'],
            ['id' => 176, 'name' => 'term-delete'],

            ['id' => 177, 'name' => 'online-registration-list'],
            ['id' => 178, 'name' => 'student-leave-approve'],
            ['id' => 179, 'name' => 'staff-leave-list'],
            ['id' => 180, 'name' => 'assign-elective-subjects'],
            ['id' => 181, 'name' => 'view-exam-result'],
            ['id' => 182, 'name' => 'assign-class-subject'],
            ['id' => 183, 'name' => 'id-card-setting'],
            ['id' => 184, 'name' => 'student-report'],
            ['id' => 185, 'name' => 'attendance-staff'],
        ];
        foreach ($permissions as $permission) {
            Permission::firstOrCreate(['name' => $permission['name']], ['guard_name' => 'web']);
        }

        // Every school's system roles and their permissions.
        app(SchoolRoles::class)->seedEverySchool();

        // Seed default English language so the system has a usable locale out of the box.
        Language::updateOrCreate(
            ['code' => 'en'],
            ['name' => 'English', 'file' => 'en.json', 'status' => 0, 'is_rtl' => 0],
        );
        foreach (['af' => 'Afrikaans', 'zu' => 'isiZulu'] as $code => $name) {
            Language::firstOrCreate(['code' => $code], ['name' => $name, 'file' => $code.'.json', 'status' => 0, 'is_rtl' => 0]);
        }

        // Change system version here
        Settings::updateOrCreate(['type' => 'system_version'], ['message' => '3.5.0']);

        // Default ID card settings so /id-card-pdf works out of the box on a fresh install.
        // firstOrCreate (not updateOrCreate): this seeder also re-runs on every deploy
        // (docker/entrypoint.sh), and must not overwrite a school's already-configured card design.
        $idCardDefaults = [
            'header_color' => '#07a5a5',
            'footer_color' => '#56cc99',
            'header_footer_text_color' => '#ffffff',
            'layout_type' => 'vertical',
            'profile_image_style' => 'round',
            'card_width' => '53.98',
            'card_height' => '85.6',
            'student_id_card_fields' => 'student_name,gr_no,class_section,roll_no,dob,blood_group,gender,session_year,guardian_name,guardian_contact,address',
            'staff_id_card_fields' => 'staff_name,designation,qualification,email,mobile,dob,gender,address',
            'background_transparency' => '80',
            'background_size' => '80',
        ];
        foreach (School::query()->orderBy('id')->pluck('id') as $schoolId) {
            app(CurrentSchool::class)->run((int) $schoolId, function () use ($idCardDefaults) {
                foreach ($idCardDefaults as $type => $message) {
                    Settings::firstOrCreate(['type' => $type], ['message' => $message]);
                }
            });
        }

        // clear cache
        Artisan::call('view:clear');
        Artisan::call('route:clear');
        Artisan::call('config:clear');
        Artisan::call('cache:clear');
    }
}
