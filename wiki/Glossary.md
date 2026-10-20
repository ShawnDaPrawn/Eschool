Short definitions of the domain terms used in the admin panel code, the API and the user interface, each with the model and table (or setting) that holds it. Terms are alphabetical. For how the academic terms fit together see [Academic Structure](Academic-Structure); for every column see [Database Schema](Database-Schema).

| Term | Meaning | Model / table |
|---|---|---|
| Academic calendar | A PDF, generated on request, listing the current session year's holidays, events, exam dates and semester breaks by month (`AcademicCalendarService`). The old `academic_calendars` table is not used | none (built from `holidays`, `events`, `exam_timetables`, `semesters`) |
| Admission number | The student's school number, generated as the current session year's name followed by the next student id. Also stored in the student's `users.email`, because students log in with it. Called "GR number" (`gr_number`, `gr_no`) in the app and on PDFs | `students.admission_no` |
| Announcement | A message from the school or a teacher, attached through a polymorphic `table_type`/`table_id` to a class, a class section or a subject-teacher assignment, with optional files | `Announcement` / `announcements` |
| Application type | `offline` for students added by staff, `online` for students who applied through the public registration form and await approval | `students.application_type` |
| Assignment | Homework for a class section and subject, with due date, points and optional resubmission | `Assignment` / `assignments` |
| Assignment submission | A student's answer to an assignment. Status 0 pending/in review, 1 accepted, 2 rejected, 3 resubmitted | `AssignmentSubmission` / `assignment_submissions` |
| Attendance type | Daily student attendance value: 0 absent, 1 present, 3 holiday, 4 first half, 5 second half (`Attendance::TYPE_*`) | `attendances.type` |
| Category | A student category (for example a caste or quota category) chosen on admission | `Category` / `categories` |
| Choiceable fee | A fee that is optional for the student (`choiceable = 1`); compulsory fees have `choiceable = 0`. Optional fees a student pays are recorded in `fees_choiceables` | `fees_classes.choiceable`, `FeesChoiceable` / `fees_choiceables` |
| Class | A grade or year group, belonging to one medium and optionally a stream, shift and educational program. Class names may contain only letters, digits and underscores | `ClassSchool` / `classes` |
| Class section | A class combined with a section (for example "10 A"). Students, timetables, attendance, lessons and assignments attach to class sections | `ClassSection` / `class_sections` |
| Class session config | Whether a class uses semesters in a given session year (`include_semesters`) | `ClassSessionConfig` / `class_session_configs` |
| Class subject | A subject offered by a class in a session year (and semester), either core (`Compulsory`) or `Elective` | `ClassSubject` / `class_subjects` |
| Class teacher | The teacher responsible for a class section in a session year (and semester). Takes attendance, enters marks for the whole section and approves its student leaves; gets the direct permissions `class-teacher` and `student-leave-approve` | `ClassTeacher` / `class_teachers` |
| Compulsory fee payment mode | Setting that, when 1, makes the apps report `is_fee_payment_due` from the fee due date or the next unpaid installment, after the free app use date | `settings` row `compulsory_fee_payment_mode` |
| Core subject | A class subject every student of the class takes (`type = 'Compulsory'`) | `class_subjects` |
| Current session year | The session year the apps and most screens use, stored as a setting; `session_years.default` mirrors it | `settings` row `session_year` |
| Custom role | A role created on the Roles page and given to staff; can hold only permissions the Super Admin role has | `roles.custom_role = 1` |
| Due charges | Late-payment charge. Configured as a percentage on the session year (`fee_due_charges`) and on each installment (`due_charges`); charged amounts are stored on `fees_paids.due_charges`, `paid_installment_fees.due_charges` and `fees_choiceables` rows with `is_due_charges = 1` | `session_years`, `installment_fees`, fee tables |
| Dynamic fields | Values of custom form fields, stored as a JSON array | `students.dynamic_fields`, `parents.dynamic_fields`, `teachers.dynamic_fields` |
| Educational program | A programme listed on the public website, optionally linked to classes | `EducationalProgram` / `educational_programs` |
| Elective subject group | A set of elective subjects for a class from which a student chooses `total_selectable_subjects` out of `total_subjects` | `ElectiveSubjectGroup` / `elective_subject_groups` |
| Exam (offline) | A written exam sat by one or more classes; has a timetable of papers and a `publish` flag | `Exam` / `exams`, `ExamClass` / `exam_classes` |
| Exam key | Numeric code a teacher gives students to open an online exam; checked by the server and never sent to the app | `online_exams.exam_key` |
| Exam marks | A student's marks for one paper, with `passing_status` and grade | `ExamMarks` / `exam_marks` |
| Exam result | A student's total, percentage and grade for a whole exam, created when the exam is published | `ExamResult` / `exam_results` |
| Exam timetable | One paper of an exam: class, subject, date, times, total and passing marks | `ExamTimetable` / `exam_timetables` |
| Fees class | The amount of a fee type charged to a class in a session year | `FeesClass` / `fees_classes` |
| Fees paid | A student's compulsory-fee payment summary for a session year, with `is_fully_paid` | `FeesPaid` / `fees_paids` |
| Fees type | A fee head such as tuition or transport | `FeesType` / `fees_types` |
| Form field | A custom field added to the student (`for = 1`), parent (2), teacher (3) or online registration (4) form. Not related to `App\Services\FormService`, which is the HTML form builder behind the `Form` facade used in Blade views | `FormField` / `form_fields` |
| Free app use date | Until this date the apps do not flag compulsory fees as due, even when compulsory fee payment mode is on | `session_years.free_app_use_date` |
| GR number | See admission number | `students.admission_no` |
| Grade | A percentage band (`starting_range` to `ending_range`) and its label, per session year | `Grade` / `grades` |
| Guardian | A parent record linked to a student as guardian rather than father or mother. On admission `parent_guardian_type` is `parent` (father and mother) or `guardian` | `Parents` / `parents`, `students.guardian_id` |
| Holiday | A dated school holiday in a session year | `Holiday` / `holidays` |
| Holiday days | Weekdays treated as off days for leave counting, stored comma-separated (for example `Sunday`) | `leave_masters.holiday_days` |
| Installment | One part of a session year's fees with its own due date and due charges. Used when `include_fee_installments` is set on the session year | `InstallmentFee` / `installment_fees`, `PaidInstallmentFee` / `paid_installment_fees` |
| Leave | A leave request by a teacher, staff member or student (for a student, sent by the student or parent). Status `0` pending, `1` approved, `2` rejected | `Leave` / `leaves`, `LeaveDetail` / `leave_details` |
| Leave detail | One day of a leave request; `type` is `Full` or a half-day value | `LeaveDetail` / `leave_details` |
| Leave master | Leave settings for a session year: allowed leaves per month (`total_leave`) and holiday days | `LeaveMaster` / `leave_masters` |
| Lesson | A unit of teaching material for a class section and subject, with files | `Lesson` / `lessons` |
| Live class link | Optional online-class URL and label on a timetable period | `timetables.live_class_url`, `timetables.link_name` |
| Medium | Language of instruction. Every class and subject belongs to one medium | `Mediums` / `mediums` |
| Online exam | A timed multiple-choice exam taken in the student app, for a whole class or one class section | `OnlineExam` / `online_exams` |
| Online registration | Admission request submitted on the public website; creates a student with `application_type = 'online'` and no section until approved | `students`, `form_fields` with `for = 4` |
| Parent | A father, mother or guardian contact record, usually with its own login (role `Parent`) used in the student/parent app | `Parents` / `parents` |
| Payment transaction | One attempt to pay fees (online or recorded cash/cheque), with gateway, status and amount | `PaymentTransaction` / `payment_transactions` |
| Promotion | Moving students from one session year to the next (same or new class section, or marking them as left) | `StudentSessionController`, `student_sessions` |
| Publish (exam) | Turning marks into `exam_results` and making them visible in the apps; unpublishing deletes the results | `exams.publish` |
| Question bank | Online exam questions stored per class subject and session year, with options and correct answers | `OnlineExamQuestion` / `online_exam_questions` |
| Question choice | A bank question placed in a specific online exam, with its marks | `OnlineExamQuestionChoice` / `online_exam_question_choices` |
| Reset request | Flag set when a student asks for a password reset from the app; cleared when an admin resets it | `users.reset_request` |
| Roll number | The student's number within a class section; assigned as the section's highest roll number plus one and can be renumbered | `students.roll_number` |
| Section | A section label such as A or B, combined with classes into class sections | `Section` / `sections` |
| Semester | A term inside a session year with start and end dates; only affects classes that have semesters switched on | `Semester` / `semesters` |
| Session year | An academic year with start and end dates, fee due date and charges | `SessionYear` / `session_years` |
| Shift | A school shift (for example morning or afternoon) with times, assigned to classes | `Shift` / `shifts` |
| Slider | A banner image for the app, the website or both (`type` 1, 2, 3) | `Slider` / `sliders` |
| Staff | A non-teaching admin panel user with a custom role | `Staff` / `staffs` |
| Stream | An optional specialisation such as Science or Commerce. Creating a class with several streams creates one class per stream | `Stream` / `streams` |
| Student session | A student's placement for one session year: class section, status (continuing or left) and result (pass or fail) | `StudentSessions` / `student_sessions` |
| Student subject | An elective subject picked by a student | `StudentSubject` / `student_subjects` |
| Subject | A subject in the catalogue of a medium; `type` is `Theory` or `Practical` | `Subject` / `subjects` |
| Subject teacher | The teacher of a subject in a class section for a session year (and semester) | `SubjectTeacher` / `subject_teachers` |
| Teacher | A teaching user with the `Teacher` role and a teacher profile | `Teacher` / `teachers` |
| Timetable | The weekly schedule of a class section; each period points at a subject-teacher row | `Timetable` / `timetables` |
| Topic | A sub-part of a lesson with its own files | `LessonTopic` / `lesson_topics` |
| Type of fee | What a payment covers: 0 compulsory in full, 1 installments, 2 optional fees | `payment_transactions.type_of_fee` |
| Web settings | Editable sections of the public website (about us, programs, FAQs, registration and so on) | `WebSetting` / `web_settings` |

## See also

- [Academic Structure](Academic-Structure)
- [Database Schema](Database-Schema)
- [Roles and Permissions](Roles-and-Permissions)
- [Exams and Grading](Exams-and-Grading)
- [Payments](Payments)
- [Home](Home)
