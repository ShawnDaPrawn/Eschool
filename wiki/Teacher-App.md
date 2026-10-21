This page covers the teacher Flutter app in `e-school-teacher/` (Dart package `eschool_teacher`, version `3.5.0+32`): login, the home screen, every route and screen with its Cubits and repositories, and how each teacher feature (class attendance, assignments, lessons and topics, announcements, exams and marks, leaves, chat, student details, the teacher's own attendance) maps to the `/api/teacher/...` endpoints. Architecture shared with the student/parent app is on [Mobile-Apps-Overview](Mobile-Apps-Overview); the endpoints are documented on [API-Teacher](API-Teacher).

## Login and session

| Step | Code | Behaviour |
|---|---|---|
| Login screen | `lib/ui/screens/login/loginScreen.dart` (`Routes.login`) | Email and password. `SignInCubit` → `AuthRepository.signInTeacher()` posts `email`, `password`, `fcm_id`, `device_type` to `teacher/login`. Demo values `defaultTeacherEmail` / `defaultTeacherPassword` are pre-filled only when `showDefaultCredentials` is `true` (it is `false`). |
| Forgot password | `login/widgets/forgotPasswordRequestBottomsheet.dart` | `ForgotPasswordRequestCubit` → POST `forgot-password` with `email`. |
| Store session | `AuthCubit.authenticateUser(jwtToken, teacher)` | Hive `authBox`: `jwtToken`, `isLogIn`, `teacherDetails`. |
| Push topics | `NotificationUtility.subscribeOrUnsubscribeToNotificationTopics()` | `teachersAndroid`/`teachersIOS` and `allAndroid`/`allIOS`. |
| First sign-in | `login/policyScreen.dart` (`Routes.policies`) | After a successful login, and on a later launch only when acceptance is still missing. GET and POST `teacher/policy-acceptance`. NavigateEd, then the school's privacy notice and terms when the school has written either one. "Not now" signs out. | 
| Splash | `splashScreen.dart` | Fetches the server configuration. A signed-in teacher who has already accepted goes on to the profile refresh (GET `teacher/get-profile-details`; a failed fetch signs the user out) and `Routes.home`. Otherwise the terms screen, or `Routes.login` when nobody is signed in. |
| Logout | `home/widgets/logoutButton.dart` | `AuthCubit.signOut()` (POST `logout`, clear Hive, unsubscribe topics) and back to `Routes.login`. |

The configuration keys this app reads are the `teacher_*` variants (`teacher_app_version`, `teacher_force_app_update`, `teacher_app_maintenance`, ...), see [Mobile-Apps-Overview](Mobile-Apps-Overview#app-configuration-from-the-server).

## Home screen

`HomeScreen` (`lib/ui/screens/home/homeScreen.dart`) is an `IndexedStack` with a bottom navigation bar of four tabs:

| Tab (label key) | Widget | Content |
|---|---|---|
| `home` | `home/tabs/homeContainer/homeContainer.dart` | Dashboard (`DashboardCubit` → GET `teacher/dashboard`, parsed in `TeacherRepository.dashboard()`): class-teacher classes (`class_teacher`), other classes (`other_classes`), pending student leave requests (`student_leave_request`), today's timetable (`timetable`), `upcoming_exams`, staff leaves (`staff_leaves.today` / `tomorrow` / `upcoming`), `events` and `gallery`, plus the "explore academics" menu. |
| `schedule` | `home/tabs/timeTableContainer.dart` | Teacher timetable (`TimeTableCubit` → GET `teacher/teacher_timetable`); each slot can carry a live-class link edited with `UpdateTimetableLinkCubit` (POST `teacher/update-timetable-link` with `timetable_id`, `live_class_link`, `link_name`). |
| `profile` | `home/tabs/profileContainer.dart` | Profile details and "view ID card" (`DownloadIdCardCubit` → GET `teacher/id-card-pdf`, opened in `Routes.pdfFileView`). |
| `setting` | `home/tabs/settingsContainer.dart` | Notifications, app language, change password (POST `change-password`), privacy policy, terms, about us, contact us, rate us / share (open `teacher_app_link`), app version (server value). |

The "explore academics" menu in `homeContainer.dart` (`exploreAcademicsItems`) links to: assignments, announcements, chapters (lessons), topics, academic calendar, attendance (the teacher's own, `Routes.teacherAttendence`), exams, manage leaves, and, only when `DashboardCubit.primaryClass()` is not null (the teacher is a class teacher), add result (`Routes.addResultForAllStudents`) and manage student leaves (`Routes.manageStudentLeaves`).

`HomeScreen` also applies maintenance mode (`AppUnderMaintenanceContainer`) and the force-update dialog, as described on [Mobile-Apps-Overview](Mobile-Apps-Overview#maintenance-mode-and-force-update).

## Routes

All names from `Routes` in [e-school-teacher/lib/app/routes.dart](https://github.com/ShawnDaPrawn/Eschool/blob/main/e-school-teacher/lib/app/routes.dart).

| Constant | Route name | Screen | File (under `lib/ui/screens/`) |
|---|---|---|---|
| `splash` | `splash` | `SplashScreen` | `splashScreen.dart` |
| `login` | `login` | `LoginScreen` | `login/loginScreen.dart` |
| `policies` | `policies` | `PolicyScreen` | `login/policyScreen.dart` |
| `home` | `/` | `HomeScreen` | `home/homeScreen.dart` |
| `classScreen` | `/class` | `ClassScreen` | `class/classScreen.dart` |
| `subject` | `/subject` | `SubjectScreen` | `subjectScreen.dart` |
| `assignments` | `/assignments` | `AssignmentsScreen` | `assignments/assignmentsScreen.dart` |
| `assignment` | `/assignment` | `AssignmentScreen` (submissions review) | `assignment/assignmentScreen.dart` |
| `addAssignment` | `/addAssignment` | `AddAssignmentScreen` | `addOrEditAssignmentScreen.dart` |
| `announcements` | `/announcements` | `AnnouncementsScreen` | `announcementsScreen.dart` |
| `addOrEditAnnouncement` | `/addOrEditAnnouncement` | `AddOrEditAnnouncementScreen` | `addOrEditAnnouncementScreen.dart` |
| `lessons` | `/lessons` | `LessonsScreen` | `lessonsScreen.dart` |
| `addOrEditLesson` | `/addOrEditLesson` | `AddOrEditLessonScreen` | `addOrEditLessonScreen.dart` |
| `topics` | `/topics` | `TopicsScreen` | `topicsScreen.dart` |
| `topicsByLesson` | `/topicsByLesson` | `TopcisByLessonScreen` | `topicsByLessonScreen.dart` |
| `addOrEditTopic` | `/addOrEditTopic` | `AddOrEditTopicScreen` | `addOrEditTopicScreen.dart` |
| `attendance` | `/attendance` | `AttendanceScreen` (class attendance) | `attendanceScreen.dart` |
| `teacherAttendence` | `/teacherAttendence` | `TeacherAttendenceScreen` (own attendance) | `teacherAttendance/teacherAttendanceScreen.dart` |
| `searchStudent` | `/searchStudent` | `SearchStudentScreen` | `searchStudentScreen.dart` |
| `studentDetails` | `/studentDetails` | `StudentDetailsScreen` | `studentDetails/studentDetailsScreen.dart` |
| `resultList` | `/resultList` | `ResultListScreen` | `result/resultScreen.dart` |
| `addResult` | `/addResult` | `AddResultScreen` | `result/addResultOfStudentScreen.dart` |
| `addResultForAllStudents` | `/addResultForAllStudents` | `AddResultForAllStudents` | `result/addResultForAllStudentsScreen.dart` |
| `exams` | `/exam` | `ExamScreen` | `exam/examScreen.dart` |
| `examTimeTable` | `/examTimeTable` | `ExamTimeTableScreen` | `exam/examTimeTableScreen.dart` |
| `academicCalendar` | `/academicCalendar` | `AcademicCalendarScreen` | `academicCalendar/academicCalendarScreen.dart` |
| `eventDetails` | `/eventDetails` | `EventDetailsScreen` | `eventsDetailsScreen.dart` |
| `manageLeaves` | `/manageLeaves` | `ManageLeavesScreen` | `leave/manageLeavesScreen.dart` |
| `addLeave` | `/addLeave` | `AddLeaveScreen` | `leave/addLeaveScreen.dart` |
| `manageStudentLeaves` | `/manageStudentLeaves` | `ManageStudentLeavesScreen` | `studentLeaves/manageStudentLeavesScreen.dart` |
| `notifications` | `/notifications` | `NotificationScreen` | `notificationsScreen.dart` |
| `chatUserPage` | `/chatUserPage` | `ChatUsersScreen` | `chat/chatUsersScreen.dart` |
| `chatMessages` | `/chatMessages` | `ChatMessagesScreen` | `chat/chatMessagesScreen.dart` |
| `chatUserProfile` | `/chatUserProfile` | `ChatUserProfileScreen` | `chat/chatUserProfileScreen.dart` |
| `chatUserSearch` | `/chatUserSearch` | `ChatUsersSearchScreen` | `chat/chatUserSearchScreen.dart` |
| `gallery` | `/gallery` | `GalleryScreen` | `gallery/galleryScreen.dart` |
| `galleryAlbumPhotos` | `/galleryAlbumPhotos` | `GalleryAlbumPhotosScreen` | `gallery/galleryAlbumPhotosScreen.dart` |
| `pdfFileView` | `/pdfFileView` | `PdfFileScreen` | `fileViews/pdfFileScreen.dart` |
| `imageFileView` | `/imageFileView` | `ImageFileScreen` | `fileViews/imageFileScreen.dart` |
| `aboutUs` | `/aboutUs` | `AboutUsScreen` | `aboutUsScreen.dart` |
| `privacyPolicy` | `/privacyPolicy` | `PrivacyPolicyScreen` | `privacyPolicyScreen.dart` |
| `termsAndCondition` | `/termsAndCondition` | `TermsAndConditionScreen` | `termsAndConditionScreen.dart` |
| `contactUs` | `/contactUs` | `ContactUsScreen` | `contactUsScreen.dart` |
| `monthWiseAttendance` | `/monthWiseAttendance` | (no `case` in the switch) | pushing it shows an empty `Scaffold` |

## Screen inventory by feature

Global Cubits created in `MyApp` ([e-school-teacher/lib/app/app.dart](https://github.com/ShawnDaPrawn/Eschool/blob/main/e-school-teacher/lib/app/app.dart)): `AppConfigurationCubit`, `AppLocalizationCubit`, `AuthCubit`, `DashboardCubit`, `StudentChatUsersCubit`, `ParentChatUserCubit`, `GalleryAlbumCubit`, `GalleryVideosCubit`. Screen-level Cubits are provided in each screen's `route()`.

Most content screens are filtered by class section and subject using `SubjectsOfClassSectionCubit` (GET `teacher/subjects` with `class_section_id`) and the class list held by `DashboardCubit` (class-teacher classes plus subject-teacher classes). A `semester_id` is added to requests when a semester is selected.

### Classes and students

| Screen | Cubits | Repositories | Endpoints / behaviour |
|---|---|---|---|
| `ClassScreen` | `SubjectsOfClassSectionCubit`, `StudentsByClassSectionCubit`, `AppConfigurationCubit` | `TeacherRepository`, `StudentRepository` | Subjects tab for every teacher; students tab and the "take attendance" button only when opened with `isClassTeacher: true`. Students: GET `teacher/student-list`. |
| `SubjectScreen` | `LessonsCubit`, `AnnouncementsCubit` | `LessonRepository`, `AnnouncementRepository` | Lessons and announcements of one subject, with add buttons. |
| `SearchStudentScreen` | (none) | | Filters the student list passed in the route arguments locally (no API call); used from `ClassScreen` and `AttendanceScreen`. |
| `StudentDetailsScreen` | `StudentMoreDetailsCubit` | `StudentRepository` | GET `teacher/student-details`; links to the student's results (`Routes.resultList`). |

### Class attendance

| Screen | Cubits | Endpoints |
|---|---|---|
| `AttendanceScreen` | `ClassAttendanceCubit`, `SubmitClassAttendanceCubit`, `AppConfigurationCubit` | GET `teacher/get-attendance` (`class_section_id`, `date`) returns `attendance_reports`, `is_holiday`, `holiday` and `leave_details`; POST `teacher/submit-attendance` (`class_section_id`, `date`, `attendance[]`). |

Attendance values are defined in `lib/data/models/attendanceEnums.dart`: `AttendanceType` `absent(0)`, `present(1)`, `firstHalfAbsent(2)`, `secondHalfAbsent(3)`; approved student leaves come back as `AttendanceLeaveType` `full(1)`, `firstHalf(2)`, `secondHalf(3)` keyed by `student_id`, so students on leave can be shown accordingly. Attendance is taken from `ClassScreen` of a class the teacher is class teacher of.

### Teacher's own attendance

| Screen | Cubits | Repository | Endpoint |
|---|---|---|---|
| `TeacherAttendenceScreen` | `TeacherAttendanceCubit`, `AppConfigurationCubit` | `TeacherAttendanceRepository` | GET `teacher/attendance-report` with `month` and `year`, parsed into `TeacherAttendanceReport` (`lib/data/models/teacherAttendance.dart`). Shows a `table_calendar` month with summary widgets (`attendanceSummaryContainer.dart`, `totalAttendanceContainer.dart`). |

Staff attendance itself is recorded in the admin panel; this screen is read-only.

### Assignments

| Screen | Cubits | Repositories | Endpoints |
|---|---|---|---|
| `AssignmentsScreen` | `AssignmentCubit`, `DeleteAssignmentCubit`, `SubjectsOfClassSectionCubit`, `DashboardCubit` | `AssignmentRepository`, `TeacherRepository` | GET `teacher/get-assignment` (`class_section_id`, `subject_id`, `semester_id`, `page`); POST `teacher/delete-assignment` (`assignment_id`) |
| `AddAssignmentScreen` (create and edit) | `CreateAssignmentCubit`, `EditAssignmentCubit`, `SubjectsOfClassSectionCubit`, `DashboardCubit` | `AssignmentRepository`, `TeacherRepository` | POST `teacher/create-assignment` / `teacher/update-assignment` with `class_section_id`, `subject_id`, `name`, `instructions`, `due_date`, `points`, `resubmission`, `extra_days_for_resubmission`, `file[]` (and `assignment_id` on update) |
| `AssignmentScreen` (submissions) | `ReviewAssignmentCubit`, `EditReviewAssignmentCubit` | `ReviewAssignmentRepository` | GET `teacher/get-assignment-submission`; POST `teacher/update-assignment-submission` (`assignment_submission_id`, `status`, `points`, `feedback`) from the accept/reject bottom sheets |

Attached files of an existing assignment can be removed with `DeleteStudyMaterialCubit` (POST `teacher/delete-file`).

### Lessons, topics and study material

| Screen | Cubits | Repositories | Endpoints |
|---|---|---|---|
| `LessonsScreen` | `LessonsCubit`, `LessonDeleteCubit`, `SubjectsOfClassSectionCubit`, `DashboardCubit` | `LessonRepository`, `TeacherRepository` | GET `teacher/get-lesson`; POST `teacher/delete-lesson` (`lesson_id`) |
| `AddOrEditLessonScreen` | `CreateLessonCubit`, `EditLessonCubit` | `LessonRepository` | POST `teacher/create-lesson` / `teacher/update-lesson` (`class_section_id`, `subject_id`, `semester_id`, `name`, `description`, files) |
| `TopicsScreen`, `TopcisByLessonScreen` | `TopicsCubit`, `DeleteTopicCubit`, `LessonsCubit` | `TopicRepository`, `LessonRepository` | GET `teacher/get-topic` (`lesson_id`); POST `teacher/delete-topic` |
| `AddOrEditTopicScreen` | `CreateTopicCubit`, `EditTopicCubit`, `LessonsCubit` | `TopicRepository` | POST `teacher/create-topic` / `teacher/update-topic` |
| Study material sheets (`ui/widgets/addStudyMaterialBottomSheet.dart`, `editStudyMaterialBottomSheet.dart`) | `UpdateStudyMaterialCubit`, `DeleteStudyMaterialCubit` | `StudyMaterialRepository` | POST `teacher/update-file`, POST `teacher/delete-file` |

Study material types (`PickedStudyMaterial.toJson()` and `getStudyMaterialType()`): `1` file upload (`file`), `2` YouTube link (`link` plus `thumbnail`), `3` video upload (`file` plus `thumbnail`); any other value is treated as "other".

### Announcements

| Screen | Cubits | Endpoints |
|---|---|---|
| `AnnouncementsScreen` | `AnnouncementsCubit`, `DeleteAnnouncementCubit`, `SubjectsOfClassSectionCubit`, `DashboardCubit` | GET `teacher/get-announcement` (`class_section_id`, `subject_id`, `semester_id`, `page`); POST `teacher/delete-announcement` |
| `AddOrEditAnnouncementScreen` | `CreateAnnouncementCubit`, `EditAnnouncementCubit` | POST `teacher/send-announcement` / `teacher/update-announcement` (`class_section_id`, `subject_id`, `title`, `description`, `file[]`, `announcement_id` on update) |

### Exams and marks

| Screen | Cubits | Endpoints |
|---|---|---|
| `ExamScreen` | `ExamDetailsCubit` | GET `teacher/get-exam-list` (`status`, `publish`, `class_section_id`, `student_id`, `get_timetable`) |
| `ExamTimeTableScreen` | `ExamTimeTableCubit` | GET `teacher/get-exam-details` |
| `AddResultForAllStudents` (class teacher) | `ExamDetailsCubit`, `ExamTimeTableCubit`, `StudentsByClassSectionCubit`, `SubjectMarksBySubjectIdCubit` | POST `teacher/submit-exam-marks/subject` with query `exam_id`, `subject_id`, `class_section_id`, `semester_id` and the marks in the body |
| `AddResultScreen` (one student) | `SubjectMarksByStudentIdCubit` | POST `teacher/submit-exam-marks/student` with query `exam_id`, `student_id`, `class_section_id` |
| `ResultListScreen` | `StudentCompletedExamWithResultCubit`, `ExamDetailsCubit`, `DownloadResultPdfCubit` | GET `teacher/get-student-result` (`student_id`); GET `teacher/get-student-result-pdf` (`student_id`, base64 `pdf`) saved to `<app documents>/Exam Results/<prefix>_on_<timestamp>.pdf` |

The teacher app has no online-exam screens; online exams are created in the admin panel and taken in the student app. `StudentRepository.fetchExamResults()` still references `Api.examResults` (`teacher/exam-marks`), but nothing calls it and `admin-panel/routes/api.php` has no such route. Grading rules are on [Exams-and-Grading](Exams-and-Grading).

### Leaves

| Screen | Cubits | Endpoints |
|---|---|---|
| `ManageLeavesScreen` (own leaves) | `LeaveCubit`, `DeleteLeaveCubit`, `SessionYearCubit`, `AppConfigurationCubit` | POST `teacher/get-leave-list` (`session_year_id`, `month`) returns `leave_details`, `taken_leaves`, `monthly_allowed_leaves`; POST `teacher/delete-leave` |
| `AddLeaveScreen` | `AddLeaveCubit`, `HolidaysCubit` | POST `teacher/apply-leave`; holidays and `holiday_days` exclude non-working days |
| `ManageStudentLeavesScreen` (class teacher) | `GetStudentLeaveCubit`, `UpdateStudentLeaveStatusCubit`, `SessionYearCubit`, `DashboardCubit` | POST `teacher/get-student-leave-list`; POST `teacher/student-leave-status-update` (`leave_id`, `status`, `reason_of_rejection`) |

Pending student leave requests are also shown on the home dashboard, where they can be approved or rejected with the same Cubit.

### Calendar, events, gallery, notifications

| Screen | Cubits | Endpoints |
|---|---|---|
| `AcademicCalendarScreen` | `AcademicCalendarCubit`, `AcademicCalendarPdfDownloadCubit` | `holidays`, `get-events-list`, exam list; PDF from `teacher/academic-calendar-pdf` saved to `<app documents>/Academic Calendar/` |
| `EventDetailsScreen` | `EventDetailsCubit` | GET `get-events-details` (the server accepts GET and POST, see [Student-Parent-App](Student-Parent-App#academics)) |
| `GalleryScreen`, `GalleryAlbumPhotosScreen` | gallery Cubits | `get-gallery-albums`, `get-gallery-images`, `get-gallery-videos` |
| `NotificationScreen` | `NotificationsCubit` | GET `teacher/get-notification` (`page`) |

Push notification taps (`NotificationUtility` in `lib/utils/notificationUtils/generalNotificationUtility.dart`): `custom` opens `Routes.notifications`, `assignment_submission` opens `Routes.assignments`, `chat` opens `Routes.chatMessages` for the sender. See [Notifications](Notifications).

### Chat

Teachers chat with both students and parents. `ChatUsersCubit` is abstract with two concrete subclasses, `StudentChatUsersCubit` and `ParentChatUserCubit`, both provided globally; `ChatUsersScreen` shows them as two lists. `ChatRepository.fetchChatUsers()` calls GET `teacher/get-user-list` with `offset`, `limit` (15), `search` and `isParent` (`1` or `0`). Messages use POST `teacher/get-user-message`, `teacher/send-message` (`receiver_id`, `message`, `file[]`) and `teacher/read-all-message`. Limits come from `chat_settings` as in the student app. The unread counter in the home app bar (`homeContainerAppBarContainer.dart`) opens `Routes.chatUserPage`. See [Chat](Chat).

## Changes in 3.5.0

What changed in the teacher app for 3.5.0:

- Android build moved to the Kotlin DSL (`build.gradle.kts`, `settings.gradle.kts`).
- New teacher attendance feature: `teacherAttendanceCubit.dart`, `teacherAttendanceRepository.dart`, `teacherAttendance.dart`, `attendanceEnums.dart`, `TeacherAttendenceScreen` and its widgets, `assets/images/attendance.svg`, the `teacherAttendence` route and the `getTeacherAttendanceReport` endpoint in `api.dart`.
- ID card download: `downloadIdCardCubit.dart` (profile tab).
- Class attendance changes in `classAttendanceCubit.dart`, `submitClassAttendanceCubit.dart`, `attendanceScreen.dart`, `studentAttendanceTileContainer.dart` and `attendanceReport.dart` (half-day absence and leave types).
- New languages `ar`, `es`, `id`, `pt-BR`, `tr` (and `en`, `hi`, `ur` updated). The changelog also lists `assets/languages/er.json`, which is not in the repository; the language list is `ar, en, es, fr, hi, id, pt-BR, tr, ur` (see [Localization](Localization)).
- Updates to leaves, student leaves, student search, student details, academic calendar, PDF viewer, chat messages and study material bottom sheet.

## See also

- [Mobile-Apps-Overview](Mobile-Apps-Overview)
- [Student-Parent-App](Student-Parent-App)
- [API-Teacher](API-Teacher)
- [Exams-and-Grading](Exams-and-Grading)
- [Chat](Chat)
- [Notifications](Notifications)
- [Mobile-App-Configuration](Mobile-App-Configuration)
