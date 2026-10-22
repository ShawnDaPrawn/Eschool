import 'package:dio/dio.dart';
import 'package:eschool_core/testing.dart';

/// What the fake API answers in the smoke flow: the shapes of the server's
/// answers (admin-panel/tests/snapshots/api/), with made-up people.

const String teacherEmail = 'teacher@school.test';
const String password = 'Smoke-test-1';

Map<String, Object?> ok(Object? data, {String message = ''}) => {
  'error': false,
  'message': message,
  'code': 200,
  'data': data,
};

const Map<String, Object?> appSettings = {
  'app_link': '',
  'ios_app_link': '',
  'app_version': '1.0.0',
  'ios_app_version': '1.0.0',
  'force_app_update': '0',
  'app_maintenance': '0',
  'school_name': 'Smoke Test School',
  'school_tagline': 'Learning together',
  'session_year': {
    'id': 1,
    'name': '2026',
    'start_date': '2026-01-14',
    'end_date': '2026-12-04',
    'default': 1,
  },
  'holiday_days': 'saturday,sunday',
  'chat_settings': {
    'automatically_messages_removed_days': 30,
    'max_characters_in_text_message': 500,
    'max_file_size_in_bytes': 1048576,
    'max_files_or_images_in_one_message': 5,
  },
};

Map<String, Object?> teacher() => {
  'id': 31,
  'user_id': 31,
  'first_name': 'Sipho',
  'last_name': 'Nkosi',
  'email': teacherEmail,
  'mobile': '0840000000',
  'image': '',
  'dob': '1988-05-11',
  'gender': 'male',
  'qualification': 'BEd',
  'status': 1,
};

Map<String, Object?> dashboard() => {
  'class_teacher': [
    {
      'id': 3,
      'class_id': 2,
      'section_id': 1,
      'name': 'Grade 7 A',
      'full_name': 'Grade 7 A',
      'class': {
        'id': 2,
        'name': 'Grade 7',
        'full_name': 'Grade 7',
        'medium_id': 1,
        'medium': {'id': 1, 'name': 'English'},
        'include_semesters': 0,
        'streams': null,
      },
      'section': {'id': 1, 'name': 'A'},
    },
  ],
  'other_classes': <Object>[],
  'student_leave_request': <Object>[],
  'timetable': <Object>[],
  'upcoming_exams': <Object>[],
  'staff_leaves': {
    'today': <Object>[],
    'tomorrow': <Object>[],
    'upcoming': <Object>[],
  },
  'events': <Object>[],
};

/// `GET settings` answers by its `type`; the smoke flow only needs the
/// app's settings.
Object settings(RequestOptions request) =>
    request.queryParameters['type'] == 'app_settings'
    ? ok(appSettings)
    : ok('');

Map<String, Object> teacherRoutes() => {
  'settings': settings,
  'v2/me/context': const FakeResponse.notFound(),
  'POST teacher/login': {
    ...ok(teacher(), message: 'User logged-in!'),
    'token': 'smoke-teacher-token',
  },
  'teacher/policy-acceptance': ok({
    'navigateed': {'accepted': true},
    'school': {'required': false},
  }),
  'teacher/get-profile-details': ok(teacher()),
  'teacher/dashboard': ok(dashboard()),
};
