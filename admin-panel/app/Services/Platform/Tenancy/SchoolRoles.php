<?php

declare(strict_types=1);

namespace App\Services\Platform\Tenancy;

use App\Models\Platform\Role;
use App\Models\Platform\School;

/**
 * The roles every school starts with (Spatie teams, keyed on school_id):
 * Super Admin and Teacher with their permissions, Parent and Student. Run
 * for a new school and on every deploy (InstallationSeeder, for every
 * school), so a school's system roles keep the permissions the code expects;
 * the roles a school creates itself are never touched.
 */
class SchoolRoles
{
    public const SUPER_ADMIN = 'Super Admin';

    public const TEACHER = 'Teacher';

    public const PARENT = 'Parent';

    public const STUDENT = 'Student';

    /** The system roles and their permissions. */
    public const PERMISSIONS = [
        self::SUPER_ADMIN => self::SUPER_ADMIN_PERMISSIONS,
        self::TEACHER => self::TEACHER_PERMISSIONS,
        self::PARENT => [],
        self::STUDENT => [],
    ];

    private const SUPER_ADMIN_PERMISSIONS = [
        'profile-person-view', 'profile-person-edit', 'profile-sensitive-view', 'profile-sensitive-export',
        'attendance-staff', 'medium-list', 'medium-create', 'medium-edit', 'medium-delete',
        'section-list', 'section-create', 'section-edit', 'section-delete', 'class-list',
        'class-create', 'class-edit', 'class-delete', 'subject-list', 'subject-create',
        'subject-edit', 'subject-delete', 'teacher-list', 'teacher-create', 'teacher-edit',
        'teacher-delete', 'class-teacher-list', 'class-teacher-create', 'class-teacher-edit',
        'class-teacher-delete', 'parents-list', 'parents-edit', 'session-year-list',
        'session-year-create', 'session-year-edit', 'session-year-delete', 'student-list',
        'student-create', 'student-edit', 'student-delete', 'category-list', 'category-create',
        'category-edit', 'category-delete', 'subject-teacher-list', 'subject-teacher-create',
        'subject-teacher-edit', 'subject-teacher-delete', 'timetable-list', 'timetable-create',
        'timetable-edit', 'timetable-delete', 'holiday-list', 'holiday-create', 'holiday-edit',
        'holiday-delete', 'announcement-list', 'announcement-create', 'announcement-edit',
        'announcement-delete', 'slider-list', 'slider-create', 'slider-edit', 'slider-delete',
        'class-timetable', 'teacher-timetable', 'class-attendance', 'exam-create', 'exam-list',
        'exam-edit', 'exam-delete', 'exam-timetable-create', 'grade-create', 'setting-create',
        'fcm-setting-create', 'assignment-submission', 'email-setting-create', 'privacy-policy',
        'terms-condition', 'contact-us', 'about-us', 'student-reset-password',
        'promote-student-list', 'promote-student-edit', 'assign-class-to-new-student',
        'language-list', 'language-create', 'language-edit', 'language-delete',
        'update-admin-profile', 'fees-type', 'fees-classes', 'fees-paid', 'fees-config',
        'stream-list', 'stream-create', 'stream-edit', 'stream-delete', 'shift-create',
        'shift-list', 'shift-edit', 'shift-delete', 'form-field-list', 'form-field-create',
        'form-field-edit', 'form-field-delete', 'notification-list', 'notification-create',
        'notification-delete', 'event-list', 'event-create', 'event-edit', 'event-delete',
        'program-create', 'program-list', 'program-delete', 'program-edit', 'media-create',
        'media-list', 'media-delete', 'media-edit', 'faq-create', 'faq-list', 'faq-delete',
        'faq-edit', 'content-create', 'content-edit', 'content-list', 'role-create', 'role-list',
        'role-edit', 'role-delete', 'staff-create', 'staff-list', 'staff-edit', 'staff-delete',
        'generate-id-card', 'id-card-setting', 'generate-document', 'generate-result',
        'leave-setting-create', 'leave-list', 'leave-approve', 'leave-delete', 'chat-settings',
        'attendance-report', 'semester-list', 'semester-create', 'semester-edit',
        'semester-delete', 'online-registration-list', 'student-leave-approve',
        'assign-elective-subjects', 'view-exam-result', 'assign-class-subject', 'student-report',
    ];

    private const TEACHER_PERMISSIONS = [
        'student-list', 'subject-teacher-list', 'timetable-list', 'attendance-list',
        'attendance-create', 'attendance-edit', 'attendance-delete', 'holiday-list',
        'announcement-list', 'announcement-create', 'announcement-edit', 'announcement-delete',
        'class-timetable', 'teacher-timetable', 'class-attendance', 'assignment-create',
        'assignment-list', 'assignment-edit', 'assignment-delete', 'assignment-submission',
        'lesson-list', 'lesson-create', 'lesson-edit', 'lesson-delete', 'topic-list',
        'topic-create', 'topic-edit', 'topic-delete', 'exam-upload-marks', 'exam-result',
        'manage-online-exam', 'generate-id-card', 'generate-result', 'leave-create', 'leave-edit',
        'leave-list', 'leave-delete', 'attendance-report', 'staff-leave-list',
        'student-leave-approve', 'view-exam-result',
    ];

    public function __construct(private readonly CurrentSchool $currentSchool) {}

    /** Create or update the school's system roles. */
    public function seed(School|int $school): void
    {
        $this->currentSchool->run($school, function () {
            foreach (self::PERMISSIONS as $name => $permissions) {
                $role = Role::query()->firstOrCreate(['name' => $name, 'guard_name' => 'web']);
                $role->syncPermissions($permissions);
            }
        });
    }

    /** Every school's system roles. */
    public function seedEverySchool(): void
    {
        foreach (School::query()->orderBy('id')->pluck('id') as $schoolId) {
            $this->seed((int) $schoolId);
        }
    }
}
