A reference map of every feature module in the admin panel: for each sidebar item, the routes in [`routes/web.php`](https://github.com/ShawnDaPrawn/Eschool/blob/main/admin-panel/routes/web.php), the controller in `app/Http/Controllers` (the `Api/` controllers are covered in [API-Overview](API-Overview)), the Blade view folder under `resources/views`, the permissions the code checks, the main models and services, and behaviour worth knowing (imports, exports, PDFs, notifications). Modules are grouped and ordered as in the sidebar, [`resources/views/layouts/sidebar.blade.php`](https://github.com/ShawnDaPrawn/Eschool/blob/main/admin-panel/resources/views/layouts/sidebar.blade.php).

For end-user steps see the Docusaurus [Admin Panel](https://shawndaprawn.github.io/Eschool/admin-panel/) and [Features](https://shawndaprawn.github.io/Eschool/features/) sections. Permissions and roles are explained in [Roles-and-Permissions](Roles-and-Permissions).

## Route groups and middleware

`routes/web.php` is loaded with the `web` middleware group. `bootstrap/app.php` appends to that group `LanguageManager`, `EncryptCookies`, `AddQueuedCookiesToResponse`, `StartSession`, `ShareErrorsFromSession`, `SubstituteBindings`, `DemoMiddleware` and `RejectDangerousUploads`; the installer package prepends `ToInstallMiddleware`; `SecurityHeaders` is appended globally; CSRF is checked everywhere except `webhook/*`.

| Group in `routes/web.php` | Middleware | Contents |
|---|---|---|
| Top level | `guest` on `GET/POST login`; `throttle:login` on `POST password/email` | Login, logout, password reset, `web-set-language/{lang}`. There is no `/register` route: commit `5c48b13` removed Laravel's leftover sign-up route, controller and view, which let anyone create an account and reach the panel. |
| `['middleware' => 'language']` | `language` (`LanguageManager`) | Public website; see [Public-Website](Public-Website) |
| `['middleware' => ['Role', 'auth']]` | `Role` = `App\Http\Middleware\CheckRole`, `auth` = `App\Http\Middleware\Authenticate` | `POST set-session-year`, and a nested `language` group with every admin module below |
| Top level | none (CSRF exempt) | `webhook/*`, `response/*/success` ([Payments](Payments)) |
| Top level | none | `GET privacy-policy`, `GET terms-conditions` |
| Top level | `auth` | `GET clear` (view, route, config and cache clear; the header's Cache Clear button) |
| `['middleware' => ['auth', 'role:Super Admin']]` | Spatie `role` | `GET storage-link`, `GET migrate` (`migrate --force`), `GET seeder_install` (`db:seed --class=InstallationSeeder --force`) |
| `prefix('common')` | none | `GET common/js/lang` (`common.language.read`) |

`CheckRole` lets a signed-in user through only if they have at least one role other than `Parent` and `Student`; such users are logged out with `no_permission_message`. An unauthenticated request gets the login view. There is no `Gate::before` bypass (it is commented out in `AuthServiceProvider`), so even Super Admin only sees what its seeded permissions allow; for example Super Admin is not seeded with `attendance-create`, `lesson-*`, `topic-*`, `assignment-create` or `manage-online-exam`, which are Teacher permissions.

`DemoMiddleware`: when `DEMO_MODE=true` (`config('app.demo_mode')`), every non-GET request from a user other than `demomodeoff@gmail.com` is refused with "This is not allowed in the Demo Version." (code 112), except a fixed list of URIs (login, ID cards, certificates, `set-session-year`, `student/export`, and the apps' login, chat and leave-list endpoints).

## Common patterns

**Permission checks.** Almost every controller method starts with `Auth::user()->can('<permission>')` (or `canAny`) and redirects to `home` with `no_permission_message`, or returns JSON `{error: true, message}` for AJAX endpoints. The `ResponseService` guards do the same: `noPermissionThenSendJson()` answers with the error JSON, and `noPermissionThenRedirect()`, `noRoleThenRedirect()`, `noAnyRoleThenRedirect()` and `noAnyPermissionThenRedirect()` throw an `HttpResponseException` carrying the redirect (since commit `5c48b13`; before that they called `->send()` and the controller kept running and rendered the protected page). The resource `edit()` methods, which return a record as JSON for the edit modals, check the module's `*-edit` permission (for example `category-edit`, `medium-edit`, `teacher-edit`, `subject-teacher-edit`, `session-year-edit`). `RoleController` uses `permission:` middleware in its constructor; `LessonController`/`LessonTopicController` and `SessionYearController::store` use Form Requests whose `authorize()` checks `lesson-create`, `lesson-edit`, `topic-create`, `topic-edit`, `session-year-create`. Lookups that only fill dropdowns (such as `get-class-sections/{class_id}`, `class-subject-list/{medium_id}`, `session-year/{id}/semesters`, `exams/get-subjects/...`, `exam/get-classes/{exam_id}`) have no check beyond the route group, because several pages with different permissions share them. `tests/Feature/PermissionsTest.php` posts to a list of these routes as a user without the permissions and expects a refusal.

**Bootstrap-table lists.** List pages render a `<table data-toggle="table" data-url="..." data-side-pagination="server" data-query-params="queryParams" data-escape="true">`. The JSON endpoint (usually the controller's `show()` method behind a `*_list` / `*-list` URL) reads `offset`, `limit`, `sort`, `order`, `search` from the query string (`public/assets/js/custom/queryParams.js`) and returns `{"total": n, "rows": [...]}`, with action buttons built as HTML in an `operate` column. `data-escape="true"` and the `escapeHtml()` helpers in `public/assets/js/custom/function.js` (commit `27a3b11`) escape row text. Tables offer client-side export (`data-export-types` txt/excel). `App\Services\BootstrapTableService` (edit/delete button builders using the `<x-icon>` component) exists but no controller calls it.

**Forms.** Create/update forms post by AJAX and get `{error, message}` JSON back. Uploads go to the `public` disk (`storage/app/public/...`, served at `/storage`), after `RejectDangerousUploads` has refused script/web-page files and sanitised SVGs.

**Excel and PDF.** Imports and exports use `maatwebsite/excel` (classes in `app/Imports` and `app/Exports`); PDFs use `barryvdh/laravel-dompdf` (`Pdf::loadView()`), including ID cards built by [`IdCardService`](https://github.com/ShawnDaPrawn/Eschool/blob/main/admin-panel/app/Services/IdCardService.php).

| Class | Used by | Purpose |
|---|---|---|
| `App\Imports\StudentsImport` | `StudentController::storeBulkData` | Bulk student admission from csv/txt/xlsx/xls into one class section (creates parents, students, emails parents) |
| `App\Imports\AttendanceImport` | `AttendanceController::storeBulkData` | Bulk attendance: rows with `student_id`, `date`, `type` for a class section |
| `App\Exports\StudentsExport` | `AttendanceController::studentExport` (`POST student/export`) | Downloads `student_list.csv`: one row per student per day of a date range (`student_id`, `admission_no`, `roll_number`, `name`, `date`) to fill in for the attendance import |
| `App\Imports\OnlineExamQuestionsImport` | `OnlineExamQuestionController::storeBulkData` | Bulk online-exam questions (`question_type`, `question`, `note`, `option1...`, `correct_options` separated by `\|`) |
| `IdCardService::buildPdf($studentIds)` / `buildStaffPdf($userIds)` | `StudentController::generateIdCard`, `StaffController::generateStaffIdCard`, the `id-card-pdf` APIs | Card-sized PDFs (`students/id_card_template`, `staff/staff_id_card_template`) from the ID card settings |

The sample files the bulk pages link to (`dummy_file.xlsx` via `Storage::url('public/dummy_file.xlsx')`, and `online_exam_questions_sample.csv` on the public disk) are not in the repository; they must be supplied on the server.

## Dashboard

| Item | Routes | Controller / views | Permissions | Notes |
|---|---|---|---|---|
| Dashboard | `GET home` (`home`) | `HomeController::index`, `home.blade.php`, `resources/views/dashboard/` | Any admin user | Super Admin, Teacher and other staff get different initial data (`getDashboardInitialData`, `getTeacherDashboardInitialData`, `getStaffDashboardInitialData`) |
| Widgets | `home/widget/fees-collection`, `attendance`, `assessment`, `leaves`, `events`, `teacher-leaves`, `teacher-exams`, `teacher-assignments` (`home.widget.*`) | `HomeController` | `fees-paid`, `attendance-report`, `view-exam-result`, `leave-list` for the first four; the rest are role-based | Fees widget uses `FeesCollectionService::summary()` ([Payments](Payments#reporting-services)) |
| Session year switcher | `POST set-session-year` (`set.session.year`) | `SessionYearController::setSessionYearToSessionStorage` | Any admin user | Stores the selected year in the session (`session('session_year')`) for filtering; the system default stays in the `session_year` setting |
| Profile and password | `edit-profile`, `update-profile`, `resetpassword`, `checkPassword`, `changePassword`, `home/reset_password` | `HomeController`, `settings/update_profile.blade.php`, `settings/reset_password.blade.php` | Any admin user (the profile menu entry is shown with `update-admin-profile`) | |

## Academics

| Item | Routes | Controller / views | Permissions | Notes |
|---|---|---|---|---|
| Medium | resource `medium` (no `create`), `medium_list` | `MediumController`, `medium/` | `medium-list/create/edit/delete` | Model `Mediums` |
| Section | resource `section` (no `create`, `edit`), `section_list` | `SectionController`, `section/` | `section-list/create/edit/delete` | |
| Stream | resource `stream` | `StreamController`, `stream/` | `stream-list/create/edit/delete` | |
| Shifts | resource `shifts` | `ShiftController`, `shifts/` | `shift-list/create/edit/delete` | |
| Subject | resource `subject` (no `create`, `edit`), `subject-list` | `SubjectController`, `subject/` | page and store `subject-create`; list `subject-list` or `subject-create`; `subject-edit`, `subject-delete` | Subject images under `storage/app/public/subjects/` (SVG allowed after sanitising) |
| Semester | resource `semester` (no `create`, `edit`), `semester/show`, `GET/POST semester/class-config` | `SemesterController`, `semester/` | `semester-list/create/edit/delete`; class config read: any of `semester-list`, `exam-create`, `assign-elective-subjects`; save: `semester-edit` | `ClassSessionConfigService` / `ClassSessionConfig` decide per class and session year whether semesters apply |
| Class | resource `class` (no `create`, `edit`), `class-list`, `get-class-sections/{class_id}`, `class-subject-list/{medium_id}` | `ClassSchoolController`, `class/` | `class-list/create/edit/delete` | Models `ClassSchool`, `ClassSection` |
| Assign Class Subject | `class/subject` (`class.subject`, GET and POST), `DELETE class/subject/{class_subject_id}`, `DELETE class/subject-group/{group_id}`, `class/subject/list`, `class/subject/semester-details/{class_id}`, `class-subject-edit/{id}` | `ClassSchoolController`, `class/subject.blade.php`, `class/edit_subject.blade.php` | `assign-class-subject` | Core subjects and elective groups (`ClassSubject`, `ElectiveSubjectGroup`) per session year |
| Assign Elective Subjects | `class/select-elective-subjects`, `get-elective-subjects-by-class`, `get-elective-groups-by-class`, `students-subject-data`, `get-student-assigned-subjects`, `POST assign-elective-subjects` | `ClassSchoolController`, `select_elective_subjects/` | `assign-elective-subjects` | Writes `StudentSubject` rows |
| Assign Class Teacher | `assign/class/teacher` (`class.teacher`), `POST class/teacher/store`, `class-teacher-list`, `POST remove-class-teacher/{id}/{class_teacher_id}`, `get-class-teacher/{class_section_id}/{session_year_id}`, `get-all-class-teacher/...` | `ClassTeacherController`, `class/teacher.blade.php` | page `class-teacher-list` (sidebar shows it for `class-teacher-create`), store `class-teacher-edit`, remove `class-teacher-delete` | `ClassTeacher` rows per session year and semester |
| Assign Subject Teacher | resource `subject-teachers` (no `create`), `subject-teachers-list` | `SubjectTeacherController`, `subject/teacher.blade.php` | `subject-teacher-list/create/edit/delete` | `SubjectTeacher` per class section, subject, session year, semester |
| Assign New Student Class | `students/assign-class` (GET, POST), `students/new-student-list` | `StudentController`, `students/assign-class.blade.php` | `assign-class-to-new-student` | Places admitted students without a section |
| Promote Student | resource `promote-student` (`index`, `store`, `show`), `getPromoteData`, `promote-student-list`, `POST student-result-details` | `StudentSessionController`, `promote_student/` | `promote-student-list`, store `promote-student-edit` | Creates the next session year's `student_sessions` rows with `previous_session_year_id`, per student `status` (continue/leave) and `result` (pass/fail) |

See [Academic-Structure](Academic-Structure) for how mediums, classes, sections, streams, shifts, semesters and session years fit together.

## Students

| Item | Routes | Controller / views | Permissions | Notes |
|---|---|---|---|---|
| Student Category | resource `category` (no `create`), `category_list` | `CategoryController`, `category/` | `category-list/create/edit/delete` | |
| Student Admission | `GET students/create` (`students.create`), `POST students` | `StudentController::create` / `store`, `students/index.blade.php` | `student-create` (store needs `student-create` and `student-edit`) | Creates student and parent users (initial password = date of birth `ddmmyyyy`), `student_sessions`, dynamic fields (`form_fields.for` 1 and 2), and emails parents (template `student_registration`, see [Notifications](Notifications#which-emails-are-sent)) |
| Online Registrations | `online-registration`, `online-registration-list`, `POST update-active-status`, `DELETE permanent-delete/{id}` | `StudentController`, `students/online_registration.blade.php` | `online-registration-list`; accept/reject `student-edit`; delete `student-delete` | Applications from the public form; see [Public-Website](Public-Website#online-registration) |
| Assign Roll No | `student/assign-roll-number`, `student/list-assign-roll-number/{class_section_id}`, `POST student/store-roll-number` | `StudentController`, `students/assign_roll_no.blade.php` | `student-create` | |
| Student Details | resource `students` (`index`, `show`, `update`, `destroy`), `students-list` (`students.list`), `students-list-data`, `student-class-sections` | `StudentController`, `students/details.blade.php` | `student-list`; edit `student-edit`; delete `student-delete` | Teachers without `student-create` see only their class sections |
| Generate ID Card | `generate-id` (`generate_id.index`), `POST generate-id-card` | `StudentController::generateIdCard`, `students/generate_id.blade.php` | `generate-id-card` | `IdCardService::buildPdf()->stream('id_card.pdf')` |
| Certificates | `generate-bonafide-certificate/{id}`, `POST bonafide-certificate`, `generate-leaving-certificate/{id}`, `POST leaving-certificate` | `StudentController`, `students/bonafide_certificate.blade.php`, `leaving_certificate.blade.php` | `generate-document` | DomPDF templates `students/bonafide_template`, `leaving_template` |
| Generate Result | `student-generate-result` (`student.result.index`), `get-student-list`, `generate-result/{id}`, `generate-exam-result/{id}` | `StudentController`, `students/generate_result.blade.php` | `generate-result`; exam result PDF `view-exam-result` | DomPDF `students/result_template` |
| Students Reset Password | `reset-password` (closure, `students.reset_password`), `reset-password-list`, `POST student-change-password` | `StudentController`, `students/reset_password.blade.php` | `student-reset-password` | Lists students who asked for a reset from the app (`users.reset_request = 1`) |
| Add Bulk Data | `students/create_bulk` (`students.create-bulk-data`), `POST students/store_bulk` | `StudentController`, `students/add_bulk_data.blade.php` | sidebar: Super Admin role only; code: `student-create` (store also `student-edit`) | `Excel::import(new StudentsImport($class_section_id), $file)`; validation errors from the import are returned one at a time |

Student report (sidebar group **Reports**, permission `student-report` to show it): `GET students-report` (`students.report`, `StudentController::detail`, view `students/stu-detail.blade.php`, permission `student-list`) and `GET student-report/{id}` (`student.report`, `StudentController::showStudentReport`, permission `student-report`; tabs for personal details, attendance, fees, exams, leaves and assignments; `students/report/`). `GET student-attendance/{id}` redirects to the attendance tab.

## Teacher, parents and staff

| Item | Routes | Controller / views | Permissions | Notes |
|---|---|---|---|---|
| Teacher Create | resource `teachers` (no `create`), `teacher_list` | `TeacherController`, `teacher/` | page `teacher-list`; store needs `teacher-create` and `teacher-edit`; `teacher-edit`, `teacher-delete` | Assigns the Teacher role, dynamic fields (`for` 3), welcome email (template `staff_registration`) |
| Teacher Details | `teacher_details` (`teacher.details`) | `TeacherController::teacherListIndex`, `teacher/details.blade.php` | `teacher-list` | The sidebar group uses `@can([...])` with an array, so it only appears for users holding all of `teacher-create`, `teacher-list` and `generate-id-card` |
| Generate ID Card (teachers) | `generate_teacher_id` (`generate_teacher_id.index`) | `StaffController::generateStaffId`, `staff/generate_staff_id.blade.php` | any of `staff-list`, `generate-id-card` | Same page and PDF as staff ID cards |
| Parents | resource `parents` (`index`, `show`, `update`), `parents_list`, `parent/search` | `ParentsController`, `parents/` | `parents-list`, `parents-edit`; search: any of `parents-list`, `student-create`, `student-edit`, `online-registration-list` | Parents are created through student admission, not here |
| Role & Permission | resource `roles`, `roles-list/{id}`, `roles-data` | `RoleController`, `roles/` | constructor middleware `permission:role-list\|role-create\|role-edit\|role-delete` etc. | Only custom roles (`custom_role = 1`, not built-in) are editable, and only permissions held by the Super Admin role can be granted. See [Roles-and-Permissions](Roles-and-Permissions) |
| Staff | resource `staff` | `StaffController`, `staff/` | `staff-list/create/edit/delete` | Roles limited to custom roles other than Super Admin; only a Super Admin may change a Super Admin account; welcome email (template `staff_registration`) |
| Generate ID Card (staff) | `generate_staff_id` (`generate_staff_id.index`), `generate-staff-id/list`, `POST generate-staff-id-card` | `StaffController`, `staff/generate_staff_id.blade.php` | any of `staff-list`, `generate-id-card` | `IdCardService::buildStaffPdf()->stream('staff_id_card.pdf')` |

## Leave

| Item | Routes | Controller / views | Permissions | Notes |
|---|---|---|---|---|
| Apply Leave | resource `leave` (`index`, `store`, `show`, `destroy`) | `LeaveController`, `leave/index.blade.php` | `leave-create`, list `leave-list`, `leave-delete` | Staff leave for the logged-in user; files under `leave/` |
| Leave Setting | resource `leave-master` | `LeaveMasterController`, `leave/leave_setting.blade.php` | `leave-setting-create` | One `LeaveMaster` per session year: `total_leave` (per month) and `holiday_days` (weekdays off) |
| Leave Report | `leave-report` (`leave-report.index`), `leave-details` | `LeaveController`, `leave/leave_details.blade.php` | `leave-list` or `leave-approve` | |
| Staff Leave Requests | `leave-request` (`leave-request.index`), `leave-request-show`, `POST leave-request-update` (`leave-status.update`), `POST leave-add-admin` (`leave.store.admin`) | `LeaveController`, `leave/leave_request.blade.php` | `leave-approve` (including `leave-add-admin`) | Approving or adding a leave marks staff attendance (`markLeaveAsAttendance()` writes `StaffAttendance`). `adminLeaveStore` creates an already approved leave for the chosen `staff_id` and needs leave settings for the current session year. |
| Student Leave Requests | `student-leave-request`, `student-leave-request-list`, `POST student-leave-request-update`, `student-leave-class-sections` | `LeaveController`, `leave/student_leave_request.blade.php` | `student-leave-approve` | Pushes approval/rejection to parents ([Notifications](Notifications)) |
| (unused) | `staff-leave`, `staff-leave-list` | `LeaveController`, `leave/staff_leave.blade.php` | `staff-leave-list` | Commented "route and method is not used"; the sidebar entry is commented out |

Statuses in `leaves.status`: 0 pending, 1 approved, 3 rejected (migration comment). The apps' leave statistics come from `LeaveReportService`.

## Timetable

| Item | Routes | Controller / views | Permissions | Notes |
|---|---|---|---|---|
| Create Timetable | resource `timetable` (`index`, `store`, `destroy`), `checkTimetable`, `get-subject-by-class-section`, `get-class-semesters`, `get-class-sections-with-semesters`, `getteacherbysubject` | `TimetableController`, `timetable/index.blade.php` | `timetable-list`, `timetable-create`, `timetable-delete` | |
| Class Timetable | `class-timetable`, `gettimetablebyclass` (`get.timetable.class`) | `TimetableController`, `timetable/class_timetable.blade.php` | `class-timetable` (sidebar also for `class-teacher`) | |
| Teacher Timetable | `teacher-timetable`, `gettimetablebyteacher`, `get-timetable-by-subject-teacher-class`, `get-timetable-data/{id}` | `TimetableController`, `timetable/teacher_timetable.blade.php` | `teacher-timetable` | |
| Live class link | `POST link-url-update` | `TimetableController::linkUpdate` | `timetable-edit` | Only the period's teacher may set it, and only an http(s) URL (tested in `AcademicAuthorizationTest`) |

## Attendance

| Item | Routes | Controller / views | Permissions | Notes |
|---|---|---|---|---|
| Add Attendance | resource `attendance` (`index`, `store`, `show`), `student-list`, `getAttendanceData` | `AttendanceController`, `attendance/index.blade.php` | page `attendance-list`; save `attendance-create` / `attendance-edit` | Approved student leave overrides the submitted type; marking a student absent pushes an alert to the parents |
| Add Bulk Data | `add-bulk-attendance` (`attendance.add-bulk-data`), `attendance/create_bulk` (`attendance.create-bulk-data`), `POST attendance/store_bulk`, `POST student/export` (`student-export`) | `AttendanceController`, `attendance/add_bulk_data.blade.php` | page `attendance-list`; import `attendance-create` / `attendance-edit`; export `attendance-list` | `StudentsExport` template CSV, `AttendanceImport` |
| Staff Attendance | `staff-attendance` (`attendance.staff_attendance`), `staff-attendance/list`, `POST staff-attendance/store`, `POST staff-attendance/bulk-store`, `staff-attendance/get-attendance`, `staff-attendance/check-leave` | `StaffController` (`stafflist`, `storeAttendance` ...), `AttendanceController::checkStaffLeave`, `staff/demoattendance.blade.php` | `attendance-staff` | Model `StaffAttendance`; `StaffAttendanceController` exists but has no routes |
| Monthly Wise | `attendance-monthly` (`attendance.monthly`), `attendance/monthly-ajax`, `get-session-year-months` | `AttendanceController`, `AttendanceStudent/monthly.blade.php` | `attendance-staff` | |
| View Attendance | `view-attendance` (`attendance.view`), `student-attendance-list` | `AttendanceController`, `attendance/view.blade.php` | `attendance-list` | |
| Attendance Report | `attendance-report` (`attendace_report.index`), `student-attendance-report` | `AttendanceController`, `attendance/report.blade.php` | `attendance-report` | Teachers are limited to their sections |

## Subject lesson and assignments

| Item | Routes | Controller / views | Permissions | Notes |
|---|---|---|---|---|
| Create Lesson | resource `lesson` (no `create`, `edit`), `search-lesson`, `DELETE file/delete/{id}` (`file.delete`), `teacher-semester-subjects` | `LessonController`, `lessons/index.blade.php` | `lesson-list`, `lesson-create` / `lesson-edit` (Form Requests), `lesson-delete` | Files (`File` model, `modal_type` = lesson) under `lessons/`; `file/delete` requires `lesson-delete` and then the edit permission of the file's owner type (`lesson-edit`, `topic-edit` or `assignment-edit`) |
| Create Topic | resource `lesson-topic` (no `create`, `edit`) | `LessonTopicController`, `lessons/topic.blade.php` | `topic-list/create/edit/delete` | |
| Create Assignment | resource `assignment` (no `create`, `edit`), `assignment-class-sections` | `AssignmentController`, `assignment/index.blade.php` | `assignment-list/create/edit/delete` | Pushes and stores a notification for the class (elective: only students who take the subject) |
| Assignment Submission | `assignment-submission` (`assignment.submission`), `assignment-submission-list`, `PUT assignment-submission/{id}` | `AssignmentController`, `assignment/submission.blade.php` | `assignment-submission` | Accept/reject notifies the student |

## Exam

Details of marks, grades and results are in [Exams-and-Grading](Exams-and-Grading).

| Item | Routes | Controller / views | Permissions | Notes |
|---|---|---|---|---|
| Create Exam | resource `exams` (no `create`, `edit`), `exams/get-exams/{class_id}`, `DELETE delete-exam-class/{exam_id}/{class_id}`, `exams/get-subjects/{class_id}/{exam_id}`, `exams/get-exam-subjects/{exam_id}`, `POST exams/publish/{id}`, `exams/get-publish-exam/{class_id}` | `ExamController`, `exams/index.blade.php` | `exam-create` for all CRUD and for publishing (the seeded `exam-list/edit/delete` are not checked) | The publish button is on the exam list |
| Create Exam Timetable | resource `exam-timetable`, `POST exams/update-timetable`, `DELETE exams/delete-timetable/{id}`, `exam/get-classes/{exam_id}`, `exam/get-subjects/{class_id}`, `exam/get-exams-by-session-year/{session_year_id}` | `ExamTimetableController`, `exams/exam-timetable.blade.php` | `exam-timetable-create` (also on the resource `update`); the class and exam lookups are unguarded dropdown sources | |
| Upload Exam Marks | `exams/upload-marks`, `exams/marks-list`, `POST exams/submit-marks`, `POST exams/update-result-marks` | `ExamController`, `exams/upload-marks.blade.php` | `exam-upload-marks` | Teachers limited to their class sections and subjects |
| Exam Result | `exams/view-result` (`exams.view-result`), `exams/class-options` | `ExamController`, `exams/view-result.blade.php` | `view-exam-result` | |
| Students Exam Result | `exams/exam-result` (`exams.get-result`), `POST exams/fetch-exam-result-list`, `exams/show-result` | `ExamController`, `exams/show_exam_result.blade.php` | `exam-result`; list `generate-result` or `view-exam-result` | |
| Exam Grade | `grades` (`grades`), `grades/by-session-year/{sessionYear}`, `POST create-grades`, `DELETE destroy-grades/{grade_id}` | `ExamController`, `exams/exam-grade.blade.php` | `grade-create` | `Grade` per session year; `findExamGrade()` helper |

## Fees

Fee setup, online payments, webhooks and offline payments are documented in [Payments](Payments).

| Item | Routes | Controller / views | Permissions |
|---|---|---|---|
| Fees Type | resource `fees-type` (no `create`, `edit`) | `FeesTypeController`, `fees/fees_types.blade.php` | `fees-type` |
| Assign Fees Classes | `fees/classes` (`fees.class.index`), `fees/classes/list`, `POST class/fees-type`, `DELETE class/fees-type/{fees_class_id}` | `FeesTypeController`, `fees/fees_class.blade.php` | `fees-classes` |
| Fees Paid | `fees/paid` (`fees.paid.index`), `fees/paid/list`, `POST fees/compulsory-paid/store`, `POST fees/optional-paid/store`, `DELETE fees/paid/remove-choiceable-fees/{id}`, `DELETE fees/paid/remove-installment-fees/{id}`, `DELETE fees/paid/clear-data/{id}`, `fees/paid/receipt-pdf/{id}` | `FeesTypeController`, `fees/fees_paid.blade.php`, receipt `fees/fees_receipt.blade.php` | `fees-paid` |
| Fees Transactions Logs | `fees/transaction-logs` (`fees.transactions.log.index`), `fees/transaction-logs/list` | `FeesTypeController`, `fees/fees_transaction_logs.blade.php` | `fees-paid` |
| Fees Configuration (under System Settings) | `fees-config` (`fees.config.index`), `POST fees-config/update` | `FeesTypeController`, `fees/fees_config.blade.php` | `fees-config` |

## Online exam

| Item | Routes | Controller / views | Permissions | Notes |
|---|---|---|---|---|
| Manage Online Exam | resource `online-exam` (no `create`, `edit`), `get-subject-online-exam`, `get-class-sections-by-session-year`, `get-class-sections-by-semester`, `get-exam-question-index` (`exam.questions.index`), `POST online-exam/add-new-question`, `online-exam/get-class-subject-questions/{online_exam_id}`, `POST online-exam/store-questions-choices`, `DELETE online-exam/remove-choiced-question/{id}`, `online-exam/result/{id}`, `online-exam/result-show/{id}` | `OnlineExamController`, `online_exam/` | `manage-online-exam` | Scores use `OnlineExamScoringService` (exactly the correct option set) |
| Manage Questions | resource `online-exam-question` (no `create`), `DELETE online-exam-question/remove-option/{id}`, `DELETE online-exam-question/remove-answer/{id}` | `OnlineExamQuestionController`, `online_exam/class_questions.blade.php` | `manage-online-exam` | Jodit editor (`public/assets/jodit`); images under `online-exam-questions/` |
| Import Questions | `online-exam-question/create-bulk`, `online-exam-question/download-sample`, `POST online-exam-question/store-bulk` | `OnlineExamQuestionController`, `online_exam/bulk_questions.blade.php` | `manage-online-exam` | `OnlineExamQuestionsImport` |
| Terms & Condition | `online-exam/terms-conditions`, `POST online-exam/store-terms-conditions` | `OnlineExamController`, `online_exam/terms_conditions.blade.php` | `manage-online-exam` | Jodit editor; stored as the `online_exam_terms_condition` setting and returned by `/api/settings` |

## Communication

| Item | Routes | Controller / views | Permissions | Notes |
|---|---|---|---|---|
| Custom Notifications | `notifications/recipients/list`, resource `notifications` (`index`, `store`, `show`, `destroy`) | `NotificationController`, `notification/` | `notification-list`, `notification-create`, `notification-delete` | See [Notifications](Notifications#custom-notifications-admin-panel) |
| Announcement | resource `announcement` (no `create`, `edit`), `announcement-list`, `getAssignData`, `announcement-class-sections`, `POST announcement/update` (`announcement.custom-update`) | `AnnouncementController`, `announcement/` | `announcement-list/create/edit/delete` | Admins post to the noticeboard; teachers to their class sections (subject announcements). Attachments under `announcement/`. Push only, no stored notification |

## School calendar and website content

### Events

| Item | Routes | Controller / views | Permissions | Notes |
|---|---|---|---|---|
| Sliders | resource `sliders` (no `create`, `edit`) | `SliderController`, `sliders/` | `slider-list/create/edit/delete` | `type` 1 app, 2 website, 3 both |
| Holiday List | resource `holiday` (no `create`, `edit`), `holiday-list`, `holiday-view` | `HolidayController`, `holiday/` | `holiday-list/create/edit/delete` | `holiday-view` (read-only list) has no permission check |
| Events | resource `events`, `DELETE multiple-event/{id}` (`multiple.event.delete`), `view-schedule/{id}` (`view_schedule.index`), `POST events-update` (`events.update-schedules`) | `EventController`, `events/` | `event-list/create/edit/delete` | Single or multi-day events (`Event`, `MultipleEvent` schedule rows); images under `events/`. The `events-update` route was renamed from `events.update` in commit `e13ac7d` because it clashed with the resource route and broke `route:cache` |
| Session Years | resource `session-years` (no `create`), `session_years_list`, `POST session-years/{sessionYear}/clear-specific`, `session-year/details/{id}`, `session-year/{id}/semesters`, `DELETE remove-installment-data/{id}` | `SessionYearController`, `session_years/` | `session-year-list/create/edit/delete` (create via `StoreSessionYearRequest`) | `SessionYearService::store()` creates the year with fee due date/charges, installments and semesters, and can copy class subjects, class/subject teachers, timetables, grades, class fees and leave settings from another year (`app/Actions/Transfers/*`). `clear-specific` deletes chosen data of one year (class subjects, teachers, fees, notifications, timetable, attendance, assignments, exams, grades, announcements, holidays, events, leaves, allowed leave days). See [Academic-Structure](Academic-Structure) |
| Gallery > Photos, Videos | see [Public-Website](Public-Website#content-management-in-the-admin-panel) | `MediaController`, `web_settings/photos.blade.php`, `videos.blade.php` | `media-create/list/edit/delete` | |
| Web Settings > Content Settings, Educational Program, FAQs, Contact Us | see [Public-Website](Public-Website#content-management-in-the-admin-panel) | `WebSettingController`, `web_settings/` | `content-create/edit`, `program-*`, `faq-*`, `contact-us` | |

## System settings

Settings are rows in the `settings` table (`type`, `message`), read with `getSettings()` / `schoolSetting()`. Since commit `e13ac7d` nothing is written to `.env`; `SettingsConfigService` applies the school name, timezone, mail and Paystack settings onto config at boot. See [Configuration](Configuration).

| Item | Routes | Controller / views | Permissions | Notes |
|---|---|---|---|---|
| App Settings | `GET/POST app-settings` | `SettingController::app_index` / `app_update`, `settings/app_settings.blade.php` | `setting-create` | `app_link`, `ios_app_link`, `app_version`, `ios_app_version`, `force_app_update`, `app_maintenance` and the `teacher_*` equivalents; returned by `/api/settings?type=app_settings`. See [Mobile-App-Configuration](Mobile-App-Configuration) |
| General Settings | `GET/POST settings` | `SettingController::index` / `update`, `settings/index.blade.php` | `setting-create` | School name, email, phone, address, tagline, `time_zone`, date/time formats, `theme_color`, `secondary_color`, default `session_year`, `online_payment`, social links, `maplink`, reCAPTCHA keys, `logo1`, `logo2`, `favicon`, `login_image` (stored under `logo/` on the public disk) |
| Language Settings | resource `language` (no `create`, `edit`), `language-sample`, `language-list`, `language-download/{id}`, `set-language/{lang}` | `LanguageController`, `settings/language_setting.blade.php` | `language-list/create/edit/delete` | Uploaded JSON is moved to `resources/lang/<code>.json`, so that directory must be writable. See [Localization](Localization) |
| Notification Setting | `GET fcm-settings`, `POST notification-setting` | `SettingController`, `settings/fcm_key.blade.php` | view `fcm-setting-create`; save `setting-create` | Firebase sender id, project id, service-account JSON. See [Notifications](Notifications#firebase-credentials) |
| Chat Settings | `chat-settings`, `POST chat-settings-update`, `POST delete-chat-messages` | `SettingController`, `settings/chat_setting.blade.php` | `chat-settings` | See [Chat](Chat#admin-chat-settings) |
| Fees Configuration | `fees-config` | `FeesTypeController` | `fees-config` | See [Payments](Payments#settings-reference) |
| Email Configuration | `GET/POST email-settings` (`setting.email-config-index`), `POST verify-email-settings` | `SettingController`, `settings/email_configuration.blade.php` | `email-setting-create` | See [Notifications](Notifications#configuration) |
| Email Template | `email-tamplate` (`email.Template`), `POST email/store` | `EmailController`, `settings/email-template.blade.php` | `email-setting-create` | Jodit editor with tag buttons that insert placeholders at the cursor; templates are filled by `EmailTemplateService`, never compiled as Blade |
| ID Card Setting | `id-card-settings` (`id_card_setting.index`), `POST id-card-settings/update`, `DELETE remove-image/{type}` | `StudentController`, `students/id_card_settings.blade.php` | view `id-card-setting`; save and image removal `setting-create` | Colours, layout, card size in mm, fields (`student_id_card_fields`, `staff_id_card_fields`), signature and background image; defaults seeded by `InstallationSeeder` |
| Additional Fields | resource `form-fields`, `POST form-fields/change-rank` | `FormFieldController`, `form_fields/` | `form-field-list/create/edit/delete`; `change-rank` needs `form-field-edit` | Dynamic fields: `for` 1 student, 2 parent, 3 teacher, 4 self student registration; types text, number, textarea, dropdown, checkbox, radio, file |
| Privacy Policy, Contact Us, About Us, Terms & Condition | `privacy-policy/index` (`privacy.index`), `contact-us`, `about-us`, `terms-condition`, `POST setting-update` | `SettingController`, `settings/privacy_policy.blade.php` etc. | view `privacy-policy` / `contact-us` / `about-us` / `terms-condition`; save `setting-create` | Edited in a Jodit editor (textarea `rich_text_message`; TinyMCE was removed in commit `9ffa0ff`). App texts returned by `/api/settings?type=...`; privacy and terms are also served at `/privacy-policy`, `/terms-conditions` |
| System Update | `GET/POST system-update` (`system-update.index`, `.update`) | `SystemUpdateController`, `system-update/` | Super Admin role | See [System-Update](System-Update) |

Several pages are viewable with one permission but saved with `setting-create` (Notification Setting, ID Card Setting, the four policy pages), so a custom role needs both.

## Known gaps

- The bulk-upload sample files are not shipped (see [Common patterns](#common-patterns)).
- `GET holiday-view` (`HolidayController::holiday_view`) and the dropdown lookups listed under [Common patterns](#common-patterns) have no permission check beyond the staff-only route group.
- Several pages are viewable with one permission and saved with another (see [System settings](#system-settings)).

## See also

- [Admin-Panel-Overview](Admin-Panel-Overview)
- [Roles-and-Permissions](Roles-and-Permissions)
- [Academic-Structure](Academic-Structure), [Exams-and-Grading](Exams-and-Grading)
- [Payments](Payments), [Notifications](Notifications), [Chat](Chat), [Public-Website](Public-Website)
- [Configuration](Configuration), [System-Update](System-Update)
- [Security-Model](Security-Model)
