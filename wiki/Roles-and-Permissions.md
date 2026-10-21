This page documents authorization in the admin panel and the REST API: the spatie/laravel-permission setup, the built-in roles and every seeded permission, how controllers and middleware check them, custom roles and staff accounts, and the ownership rules that limit teachers and parents to their own classes and children. It reflects the code after the 2026 security hardening commits listed at the end. For the wider security picture (uploads, headers, webhooks) see [Security Model](Security-Model).

## Setup

- Package: `spatie/laravel-permission` `^6.0` (`composer.json`), provider registered in [`bootstrap/app.php`](https://github.com/ShawnDaPrawn/Eschool/blob/main/admin-panel/bootstrap/app.php).
- Config: [`config/permission.php`](https://github.com/ShawnDaPrawn/Eschool/blob/main/admin-panel/config/permission.php) uses the default models (`Spatie\Permission\Models\Role` / `Permission`) and table names (`roles`, `permissions`, `model_has_roles`, `model_has_permissions`, `role_has_permissions`). `teams` is `false`, `enable_wildcard_permission` is `false`, `events_enabled` is `false`, and the permission cache key is `spatie.permission.cache` with a 24 hour expiry on the default cache store.
- `App\Models\User` uses the `HasRoles` trait with `$guard_name = 'web'`. Every role and permission is on the `web` guard, and the same user model is used for Sanctum API tokens, so role checks behave the same in the panel and the API.
- `roles.custom_role` (int) marks roles created on the Roles page (1) versus seeded roles (0).
- **There is no `Gate::before` super-user rule** (it is commented out in `AuthServiceProvider`). The Super Admin role can do only what its seeded permission list allows. For example it does not hold `exam-upload-marks`, `manage-online-exam`, `lesson-*` or `attendance-*`; those are teacher work.
- Middleware aliases (in `bootstrap/app.php`): `permission`, `role`, `role_or_permission` (spatie), `Role` (`CheckRole`), `checkChild` (`CheckChild`), `checkStudent` (`CheckStudent`), `student.session.active`, `parent.children.session.active`.

## Roles

| Role | Created by | Assigned to | Can sign in to |
|---|---|---|---|
| `Super Admin` | `InstallationSeeder` | User id 1 by `AddSuperAdminSeeder` (`superadmin@gmail.com` / `superadmin`; change it) | Admin panel |
| `Teacher` | `InstallationSeeder` | Every teacher created by `TeacherController` (`$user->assignRole([2])`, which assumes the Teacher role has id 2) | Admin panel (teacher view) and the teacher app |
| `Parent` | `InstallationSeeder` (no permissions) | Father/mother/guardian users created with a student | Parent side of the student/parent app only |
| `Student` | `InstallationSeeder` (no permissions) | Student users | Student side of the app only |
| Custom roles | Roles page (`RoleController`, `custom_role = 1`) | Staff members via `StaffController` | Admin panel |

There is no built-in "Staff" role: staff accounts always get a custom role. The name `Admin` is checked in a few places (`HomeController`, and the unused `Students::scopeOfTeacher`) but no seeder creates it; a custom role named `Admin` would hit those branches.

Migration `2026_03_24_152838` grants `view-exam-result` to role ids 1 and 2, which assumes the default ids for Super Admin and Teacher.

## Permission catalogue

`InstallationSeeder` creates **174** permissions with `Permission::firstOrCreate(['name' => ...], ['guard_name' => 'web'])` (the `id` values written in its array are not used) and then calls `syncPermissions()` on the two staff roles: **151** for `Super Admin` and **41** for `Teacher`. Because the installer and every system update re-run this seeder, a role's permission list is reset to the seeded list on each update.

Columns: **SA** = granted to Super Admin, **T** = granted to Teacher. "Checked in" names the main controllers that test the permission. Permissions marked *menu only* are tested only in Blade (`@can` / `@canany`) to show a menu item; *not checked* means no code tests them at all.

### Roles, staff and settings

| Permission | SA | T | Checked in |
|---|---|---|---|
| `role-list`, `role-create`, `role-edit`, `role-delete` | Yes | | `RoleController` (constructor `permission:` middleware plus explicit checks) |
| `staff-list`, `staff-create`, `staff-edit`, `staff-delete` | Yes | | `StaffController` |
| `attendance-staff` | Yes | | Staff attendance pages in `StaffController` and `AttendanceController` |
| `setting-create` | Yes | | `SettingController` (general, app and notification settings), `Student\IdCardController` (ID-card settings, image removal) |
| `fcm-setting-create` | Yes | | `SettingController` (Firebase settings) |
| `email-setting-create` | Yes | | `SettingController` (mail settings), `EmailController` (email templates) |
| `privacy-policy`, `terms-condition`, `about-us`, `contact-us` | Yes | | `SettingController` pages; `contact-us` also gates the contact-message inbox in `WebSettingController` |
| `language-list`, `language-create`, `language-edit`, `language-delete` | Yes | | `LanguageController` |
| `chat-settings` | Yes | | `SettingController` (chat settings, which also covers deleting chat messages) |
| `id-card-setting` | Yes | | ID-card design settings (`Student\IdCardController`) |
| `update-admin-profile` | Yes | | Menu only (profile dropdown) |

### Academic setup

| Permission | SA | T | Checked in |
|---|---|---|---|
| `medium-*`, `section-*`, `class-*`, `subject-*`, `stream-*`, `shift-*` (each `-list`, `-create`, `-edit`, `-delete`) | Yes | | The matching controllers |
| `semester-list`, `semester-create`, `semester-edit`, `semester-delete` | Yes | | `SemesterController` (`semester-edit` also saves the per-class semester switch) |
| `session-year-list`, `session-year-create`, `session-year-edit`, `session-year-delete` | Yes | | `SessionYearController`, `StoreSessionYearRequest::authorize()`; `-delete` also clears data and installments |
| `assign-class-subject` | Yes | | Class subject pages in `Classes\SubjectController` |
| `assign-elective-subjects` | Yes | | Elective assignment in `Classes\ElectiveController` |
| `class-teacher-list`, `class-teacher-edit`, `class-teacher-delete` | Yes | | `ClassTeacherController` (`-edit` assigns, `-delete` removes) |
| `class-teacher-create` | Yes | | Menu only |
| `subject-teacher-list` | Yes | Yes | `SubjectTeacherController` |
| `subject-teacher-create`, `subject-teacher-edit`, `subject-teacher-delete` | Yes | | `SubjectTeacherController` (saving requires both `-create` and `-edit`) |
| `timetable-list` | Yes | Yes | `TimetableController` |
| `timetable-create`, `timetable-edit`, `timetable-delete` | Yes | | `TimetableController` (`-edit` or being the period's teacher allows changing a live-class link) |
| `class-timetable`, `teacher-timetable` | Yes | Yes | `TimetableController` timetable views |
| `holiday-list` | Yes | Yes | `HolidayController` |
| `holiday-create`, `holiday-edit`, `holiday-delete` | Yes | | `HolidayController` |
| `class-teacher` | | | Never granted through a role. Given directly to a teacher's user when they become a class teacher; used to show class-teacher menus |

### Students and parents

| Permission | SA | T | Checked in |
|---|---|---|---|
| `student-list` | Yes | Yes | `Student\StudentController` and `Student\ReportController` list and export |
| `student-create`, `student-edit`, `student-delete` | Yes | | `Student\StudentController` |
| `parents-list`, `parents-edit` | Yes | | `ParentsController` (parents are created and deleted with students, so there is no create/delete permission) |
| `category-list`, `category-create`, `category-edit`, `category-delete` | Yes | | `CategoryController` |
| `student-reset-password` | Yes | | Password reset requests from the student app (replaces `reset-password-list` and `student-change-password`) |
| `promote-student-list`, `promote-student-edit` | Yes | | `StudentSessionController` |
| `assign-class-to-new-student` | Yes | | `Student\ClassAssignmentController::assignClass`, `newStudentList`, `assignClass_store` |
| `online-registration-list` | Yes | | Online admission list, approval and rejection (`Student\OnlineRegistrationController`, `ParentsController`) |
| `form-field-list`, `form-field-create`, `form-field-edit`, `form-field-delete` | Yes | | `FormFieldController` (custom registration fields) |
| `generate-id-card` | Yes | Yes | Student and staff ID cards (`Student\IdCardController`, `StaffController`) |
| `generate-document` | Yes | | Bonafide and leaving certificates (`Student\CertificateController`) |
| `student-report` | Yes | | Menu only (Reports menu) |

### Attendance, lessons and assignments

| Permission | SA | T | Checked in |
|---|---|---|---|
| `attendance-list`, `attendance-create`, `attendance-edit` | | Yes | `AttendanceController`, teacher API attendance endpoints |
| `attendance-delete` | | Yes | Not checked |
| `class-attendance` | Yes | Yes | Not checked (only used to group permissions on the Roles page) |
| `attendance-report` | Yes | Yes | `AttendanceController`, dashboard widget |
| `lesson-list`, `lesson-create`, `lesson-edit`, `lesson-delete` | | Yes | `LessonController`, teacher API |
| `topic-list`, `topic-create`, `topic-edit`, `topic-delete` | | Yes | `LessonTopicController`, teacher API |
| `assignment-list`, `assignment-create`, `assignment-edit`, `assignment-delete` | | Yes | `AssignmentController`, teacher API |
| `assignment-submission` | Yes | Yes | Reviewing submissions (`AssignmentController`, teacher API) |
| `announcement-list`, `announcement-create`, `announcement-edit`, `announcement-delete` | Yes | Yes | `AnnouncementController`, teacher API |

### Exams

| Permission | SA | T | Checked in |
|---|---|---|---|
| `exam-create` | Yes | | Every offline-exam admin action in `Exam\ExamController` and `Exam\PublishController`: create, list, update, delete, publish/unpublish results, remove an exam class |
| `exam-list`, `exam-edit`, `exam-delete` | Yes | | Not checked (all gated by `exam-create`) |
| `exam-timetable-create` | Yes | | `ExamTimetableController` |
| `grade-create` | Yes | | Grade bands (`Exam\GradeController::indexGrades`, `createGrades`, `destroyGrades`) |
| `exam-upload-marks` | | Yes | Marks entry and correction (`Exam\MarksController`) |
| `exam-result` | | Yes | Class-teacher result view (`Exam\ResultController::getExamResultIndex`, `showExamResult`) |
| `view-exam-result` | Yes | Yes | Exam result list and per-exam result PDF (`Exam\ResultController::viewExamResult`, `fetchExamResultList`, `Student\ResultController::generateExamResult`) |
| `generate-result` | Yes | Yes | Whole-year result PDF (`Student\ResultController::generateResult`) |
| `manage-online-exam` | | Yes | Everything in `OnlineExamController` and `OnlineExamQuestionController` |

### Fees

| Permission | SA | T | Checked in |
|---|---|---|---|
| `fees-type` | Yes | | Fee types (`Fees\TypeController`) |
| `fees-classes` | Yes | | Fee amounts per class |
| `fees-paid` | Yes | | Collecting fees, receipts, transaction logs, clearing paid data, dashboard fee widget |
| `fees-config` | Yes | | Payment gateway and fee configuration |

### Communication, leave and website

| Permission | SA | T | Checked in |
|---|---|---|---|
| `notification-list`, `notification-create`, `notification-delete` | Yes | | `NotificationController` (there is no edit action) |
| `leave-setting-create` | Yes | | `LeaveMasterController` (allowed leaves and weekly holidays) |
| `leave-create` | | Yes | Applying for leave (`Leave\LeaveController::index`, `store`) |
| `leave-edit` | | Yes | Menu only |
| `leave-delete` | Yes | Yes | `Leave\LeaveController::destroy` |
| `leave-list` | Yes | Yes | Own leave list, leave report, dashboard widget |
| `leave-approve` | Yes | | Approving staff and teacher leave requests (`Leave\RequestController::leaveRequestIndex`, `leaveStatusUpdate`) |
| `student-leave-approve` | Yes | Yes | Student leave requests (`Leave\StudentLeaveController`, teacher API); also granted directly to class teachers |
| `staff-leave-list` | | Yes | `Leave\StaffLeaveController::staffLeaveIndex`, `staffLeaveList` |
| `slider-*`, `event-*`, `program-*`, `media-*`, `faq-*` (list/create/edit/delete) | Yes | | `SliderController`, `EventController`, `WebSettingController`, `MediaController` |
| `content-create`, `content-edit` | Yes | | Website section content (`WebSettingController`) |
| `content-list` | Yes | | Not checked |

### Removed permissions

These existed in older versions and are deleted by migrations (with their `role_has_permissions` and `model_has_permissions` rows): `chat-message-delete` (merged into `chat-settings`), `notification-edit`, `parents-create`, `parents-delete`, `promote-student-create`, `promote-student-delete`, `subject-lesson`, `student-assignment`, `reset-password-list`, `student-change-password` (holders are given `student-reset-password`).

## Direct per-user permissions

Two flows write to `model_has_permissions` instead of a role:

- **Class teacher.** `ClassTeacherController::assign_teacher` gives the teacher's user `class-teacher` and `student-leave-approve` when a new assignment row is created. Removing an assignment revokes both, even if the teacher still class-teaches another section. `TeacherController::destroy` revokes `class-teacher`.
- **Teacher "grant permission" option.** When a teacher is created or edited with the grant option on, `TeacherController` calls `$user->syncPermissions([...])` with `student-create`, `student-list`, `student-edit`, `student-delete`, `parents-list`, `parents-edit`; with it off, it revokes those six. `syncPermissions()` replaces all of the user's direct permissions, so saving with the option on also drops a direct `class-teacher` permission.

## How checks are written

The admin panel does not use route-level `permission:` middleware except in `RoleController`. Almost every controller action starts with an explicit check (about 380 `can()` calls under `app/Http`):

```php
if (! Auth::user()->can('exam-create')) {
    return response()->json(['error' => true, 'message' => trans('no_permission_message')]);
}
```

Other forms in use:

| Form | Where |
|---|---|
| `$this->middleware('permission:role-list\|role-create\|...', ['only' => [...]])` | `RoleController` constructor |
| `Auth::user()->canAny([...])` | `MediumController`, `SliderController`, `FormFieldController`, `EventController`, `StaffController` (ID cards), `SemesterController::getClassConfig` |
| `ResponseService::noPermissionThenSendJson('perm')` | Teacher API (assignments, lessons, topics, announcements, attendance), the `Classes\` controllers, `HomeController` widgets. Sends the JSON error and calls `exit()` |
| `ResponseService::noPermissionThenRedirect('perm')` | Redirects to `home` with `no_permission_message` and `send()`s immediately |
| `ResponseService::noAnyPermissionThenSendJson([...])` | Teacher API `submitAttendance` (`attendance-create` or `attendance-edit`) |
| `$user->hasRole('Super Admin')` | `SystemUpdateController`, dashboard branches, a few list pages |
| Form request `authorize()` | `StoreSessionYearRequest` (`session-year-create`) |
| Blade `@can` / `@canany` | `resources/views/layouts/sidebar.blade.php` and page buttons decide what is shown; the controller check is what enforces it |

`ResponseService` also has `noRoleThenRedirect()` and `noAnyRoleThenRedirect()`; they are not called anywhere.

## Admin panel access

- `routes/web.php` wraps the admin routes in `['middleware' => ['Role', 'auth']]`. `CheckRole` lets a user through only if they have at least one role other than `Parent` and `Student`. A signed-in user without one is logged out and sent to the login page with `no_permission_message`; a guest gets the login view.
- `LoginController::authenticated()` logs out Parent and Student users straight after login with `app_only_login_not_allowed_on_web`.
- The self-registration route (`/register`) has been removed.
- Maintenance routes `storage-link`, `migrate` and `seeder_install` are in a group with `['auth', 'role:Super Admin']`; `POST /clear` (the header cache button, shown to Super Admins only) needs `auth` and `role:Super Admin` too.
- `SystemUpdateController` refuses anyone without the `Super Admin` role.
- Login, password-reset and app-login endpoints use the `login` rate limiter (`RouteServiceProvider`): 5 attempts a minute per path and account plus 20 a minute per IP. Default passwords are dates of birth, which is why the per-account limit exists.

## API access by role

All authenticated API groups use Sanctum tokens (`auth:sanctum`) plus spatie's `role:` middleware, so a token only works on its own role's endpoints.

| Route prefix | Middleware | Extra per-route checks |
|---|---|---|
| `POST /api/student/login`, `forgot-password` | `throttle:login` | Login refuses non-students, `users.status != 1`, and students with no active `student_sessions` row for the current year before issuing a token |
| `/api/student/*` | `auth:sanctum`, `role:Student`, `student.session.active` | Queries are built from the caller's own student record; online exam, elective, topic, announcement and submission endpoints are limited to the student's class and subjects |
| `POST /api/parent/login` | `throttle:login` | Refuses non-parents |
| `/api/parent/*` | `auth:sanctum`, `role:Parent`, `parent.children.session.active` | Child-specific routes add `CheckChild`; others resolve children through `Parent\Concerns\ParentAccess::resolveChild()` / `parentChildIds()` |
| `POST /api/teacher/login` | `throttle:login` | Refuses non-teachers |
| `/api/teacher/*` | `auth:sanctum`, `role:Teacher` | Permission checks (`noPermissionThenSendJson`) plus ownership helpers; `get-student-result` and `get-student-marks` add `CheckStudent` |
| `POST /api/logout`, `POST /api/change-password` | `auth:sanctum` | |
| Public: `holidays`, `sliders`, `current-session-year`, `settings`, `get-events-list`, `get-events-details`, `get-session-year`, `get-semesters`, gallery endpoints, `forgot-password` | none (`forgot-password` is throttled) | |

The API group also has the default `throttle:api` limiter: 60 requests a minute per user or IP.

### Middleware

- **`CheckChild`** reads `child_id`, validates it as an integer and looks it up with `$parent->children()->whereKey($childId)` (father, mother or guardian in one OR query). On failure it returns `{"error": true, "message": "Invalid Child ID Passed.", "code": 105}`. On success the resolved child is stored as the request attribute `child`.
- **`CheckStudent`** (teacher API) finds the caller's teacher id, the class sections they are class teacher of (`class_teachers`, any session year) and the `students.class_section_id` of `student_id`; if it is not one of them it returns `Invalid Student ID Passed.` with code 105.
- **`EnsureStudentSessionIsActive`**, **`EnsureParentChildrenSessionIsActive`**: see [Academic Structure](Academic-Structure#session-middleware-for-the-apps).

### Ownership scoping

Permissions say what kind of action a teacher may take; ownership rules say on which records.

- **Teacher API** (`Teacher\Concerns\TeacherAccess` trait, used by the `Teacher\` controllers): `currentTeacher()` refuses a Teacher-role user with no `teachers` row (JSON error with `code` 403). `isClassTeacherOf($classSectionId)` and `teachesSubject($classSectionId, $subjectId, $semesterId)` check the current session year. `teacherClassSectionIds()` is the union of class-teacher and subject-teacher sections; `teachesStudent($studentId)` intersects it with the student's sections. Rules applied with them: student details, guardian contacts and result PDFs only for students the teacher teaches; attendance only as class teacher of that section; marks only as class teacher or subject teacher, for students in the section, within 0 and the paper's total, and not after the exam is published; student leave approval only as the student's class teacher and with `student-leave-approve`; lessons, topics, assignments, submissions and announcements through the model ownership scopes; files only when attached to the teacher's own lessons, topics, assignments, announcements or leaves (`canManageFile()`); live-class links only on the teacher's own periods; chat only with the teacher's students, their parents or people who already wrote to them (`canChatWith()`).
- **Admin panel**: model scopes such as `Lesson::lessonTeachers`, `Assignment::assignmentTeachers`, `ClassSection::subjectTeacher` and `SubjectTeacher::subjectTeacher` (listed on [Database Schema](Database-Schema#scoping-summary)) narrow lists for Teacher-role users. `Exam\MarksController::teacherMarksClassSectionIds()` limits marks entry and correction to sections the teacher class-teaches or teaches the subject in, and existing marks rows can only be overwritten for the same timetable and student.
- **Parent API**: every endpoint resolves the child through the parent's own children; fee receipts, payment status, failing a transaction and paying fees only work on the parent's own children's transactions; chat only with the child's teachers; online exam results and lesson topics are limited to the child's classes.

## Custom roles (RoleController)

[`RoleController`](https://github.com/ShawnDaPrawn/Eschool/blob/main/admin-panel/app/Http/Controllers/RoleController.php) manages roles with `custom_role = 1` only.

- `BUILT_IN_ROLES = ['Super Admin', 'Teacher', 'Parent', 'Student']`. A new or renamed role may not use these names, and `isEditableRole()` refuses to edit or delete any role that is not `custom_role = 1` or is one of these names.
- **Assignable permissions** are exactly the permissions currently held by the `Super Admin` role (`loadAssignablePermissions()`). Requested ids outside that set are dropped (`assignablePermissionsFrom()`). Teacher-only permissions such as `attendance-*`, `lesson-*`, `assignment-create` or `manage-online-exam` therefore cannot be given to a custom role.
- The index and the data table only list custom roles. The create/edit forms group permissions by resource (`groupPermissions()`), using suffixes `-list`, `-create`, `-edit`, `-delete`, `-approve`, prefixes `attendance`, `exam`, `fees` and a few manual mappings.

## Staff accounts (StaffController)

Staff are non-teaching users of the admin panel (for example an accountant or receptionist). [`StaffController`](https://github.com/ShawnDaPrawn/Eschool/blob/main/admin-panel/app/Http/Controllers/StaffController.php):

- `store` (permission `staff-create`) requires `role_id` to pass `assignableRoleRule()`: it must exist with `custom_role = 1` and not be named `Super Admin`. It creates a `users` row (initial password: date of birth as `ddmmyyyy`), assigns the role, creates a `staffs` row and sends the `staff_registration` email template.
- If the email belongs to a soft-deleted user, that user is restored instead and `syncRoles($role)` replaces whatever roles they had before deletion. The restore path only revives an existing soft-deleted `staffs` row; because the `Staff` model does not use `SoftDeletes`, deleting staff removes that row outright, so a restored user may be left without a `staffs` row.
- `update` (`staff-edit`) and `destroy` (`staff-delete`) go through `canManageStaffUser()`: the target must have a `staffs` row, and only a Super Admin may change a Super Admin account. Nobody can delete themself.
- Staff attendance (`staff_attendances`) is managed on pages gated by `attendance-staff`.
- Staff log in to the admin panel; there is no staff app. What they see is decided entirely by their custom role.

## Hardening history

These commits changed authorization behaviour; the rules above describe the result.

| Commit | Change |
|---|---|
| `2eceb8b` | `/clear` needs login; `/storage-link`, `/migrate`, `/seeder_install` need Super Admin |
| `2de82e8` | App tokens expire after 30 days (`SANCTUM_EXPIRATION`) and are pruned daily |
| `97e6bff` | Removed the unprotected `UserController` and `resources/views/users` (from the spatie tutorial), which let any logged-in user create a Super Admin |
| `ef7df75` | Email template editor needs `email-setting-create`; templates are filled by `EmailTemplateService` instead of `Blade::render` |
| `439395d` | Student, parent and teacher API groups require the matching role; `Parents::children()` fixed (it was a UNION that let mothers and guardians pass any `child_id`); `CheckChild` validates the id; login and reset throttling; `/register` removed; admin panel requires a staff role |
| `201b863` | `RoleController` only grants assignable permissions and never edits built-in roles; `StaffController` cannot assign Super Admin or touch Super Admin accounts and resets roles on restore; staff attendance needs `attendance-staff`; slider and parent search need permissions; language codes validated; password change checks the current password |
| `77fe8ed` | Teacher API limited to the teacher's own classes, subjects, students, files and chats |
| `a09dfe8` | Lesson, topic and submission scopes match the exact class-section and subject pair |
| `c890813` | Parent API limited to the parent's own children and transactions |
| `6b9f7dd` | Missing permission checks added across academic pages (student deletion, online registrations, elective groups, class teachers, exam publishing, settings image removal, ID cards, result history, attendance, exports); teacher marks limited to their sections; SQL injection in eight list searches fixed |
| `839c9eb` | Payment webhooks fail closed and are idempotent, server-side fee pricing, online exam answer hiding and exact-set scoring, student API ownership checks |

## Permission migrations

| Migration | Effect |
|---|---|
| `2026_03_24_152838_add_view_exam_result_permission_to_permissions_table` | Creates `view-exam-result` and grants it to role ids 1 and 2 if they exist |
| `2026_06_17_120000_remove_chat_message_delete_permission` | Deletes `chat-message-delete` |
| `2026_06_17_130000_remove_notification_edit_permission` | Deletes `notification-edit` |
| `2026_06_17_140000_remove_parent_create_delete_permissions` | Deletes `parents-create`, `parents-delete` |
| `2026_06_17_150000_remove_promote_student_create_delete_permissions` | Deletes `promote-student-create`, `promote-student-delete` |
| `2026_06_17_160000_add_id_card_setting_permission` | Creates `id-card-setting` and grants it to `Super Admin` |
| `2026_06_17_170000_remove_subject_lesson_permission` | Deletes `subject-lesson` |
| `2026_06_17_180000_remove_student_assignment_from_superadmin` | Deletes `student-assignment` entirely |
| `2026_06_18_120000_remove_redundant_reset_password_permissions` | Gives `student-reset-password` to every role or user that held `reset-password-list` or `student-change-password`, then deletes those two |

Each removal migration also calls `PermissionRegistrar::forgetCachedPermissions()`.

### Adding a permission

1. Add it to the `$permissions` array in `InstallationSeeder` and, if a built-in role should have it, to `$superadmin_permission_list` or `$teacher_permissions_list`. The seeder runs on fresh installs and on every in-panel update.
2. For installs upgraded with `php artisan migrate` only, add a data migration in the style of `2026_06_17_160000_add_id_card_setting_permission.php`.
3. Check it in the controller action (`Auth::user()->can(...)`) and, for menus, in the Blade view. Remember that a permission granted to Super Admin automatically becomes assignable to custom roles.

## See also

- [Security Model](Security-Model)
- [Academic Structure](Academic-Structure)
- [Database Schema](Database-Schema)
- [API Overview](API-Overview)
- [API Teacher](API-Teacher)
- [API Parent](API-Parent)
- [Admin Modules](Admin-Modules)
