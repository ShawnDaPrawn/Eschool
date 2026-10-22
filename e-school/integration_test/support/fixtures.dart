import 'package:dio/dio.dart';
import 'package:eschool_core/testing.dart';

/// What the fake API answers in the smoke flows: the shapes of the server's
/// answers (admin-panel/tests/snapshots/api/), with made-up people.

const String grNumber = 'GR-0001';
const String parentEmail = 'parent@school.test';
const String password = 'Smoke-test-1';

Map<String, Object?> ok(Object? data, {String message = ''}) => {
  'error': false,
  'message': message,
  'code': 200,
  'data': data,
};

const Map<String, Object?> sessionYear = {
  'id': 1,
  'name': '2026',
  'start_date': '2026-01-14',
  'end_date': '2026-12-04',
  'default': 1,
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
  'session_year': sessionYear,
  'holiday_days': 'saturday,sunday',
  'online_payment': '0',
  'compulsory_fee_payment_mode': '0',
  'is_student_can_pay_fees': '0',
  'chat_settings': {
    'automatically_messages_removed_days': 30,
    'max_characters_in_text_message': 500,
    'max_file_size_in_bytes': 1048576,
    'max_files_or_images_in_one_message': 5,
  },
  'current_semester': <String, Object?>{},
};

Map<String, Object?> student({int id = 11, String firstName = 'Thandi'}) => {
  'id': id,
  'user_id': id,
  'first_name': firstName,
  'last_name': 'Mokoena',
  'full_name': '$firstName Mokoena',
  'email': 'thandi@school.test',
  'mobile': '0820000000',
  'image': '',
  'dob': '2014-03-02',
  'gender': 'female',
  'status': 1,
  'admission_no': grNumber,
  'admission_date': '2026-01-14',
  'roll_number': 4,
  'class_section_id': 3,
  'class_section_name': 'Grade 7 A',
  'medium_name': 'English',
  'category_name': 'General',
  'is_new_admission': 0,
  'is_fee_payment_due': 0,
  'is_semester_on_in_class': false,
};

Map<String, Object?> parent() => {
  'id': 21,
  'user_id': 21,
  'first_name': 'Lerato',
  'last_name': 'Mokoena',
  'email': parentEmail,
  'mobile': '0830000000',
  'image': '',
  'dob': '1985-07-19',
  'gender': 'female',
  'occupation': 'Nurse',
  'status': 1,
  'children': [student()],
};

const Map<String, Object?> subject = {
  'id': 5,
  'name': 'Mathematics',
  'code': 'MATH',
  'bg_color': '#5c6bc0',
  'image': '',
  'medium_id': 1,
  'type': 'Theory',
};

Map<String, Object?> dashboard() => {
  'announcements': [
    {
      'id': 1,
      'title': 'Sports day',
      'description': 'Sports day is on Friday.',
      'file': <Object>[],
      'created_at': '2026-10-20T08:00:00.000000Z',
      'session_year_id': 1,
      'table_type': 'App\\Models\\ClassSection',
    },
  ],
  'assignments': <Object>[],
  'events': <Object>[],
  'exams': <Object>[],
  'gallery': {'albums': <Object>[], 'videos': <Object>[]},
  'sliders': <Object>[],
  'subjects': {
    'core_subject': [
      {
        'id': 1,
        'class_id': 3,
        'subject_id': 5,
        'session_year_id': 1,
        'type': 'Compulsory',
        'subject': subject,
      },
    ],
    'elective_subject': null,
  },
  'timetables': [
    {
      'day': 1,
      'day_name': 'Monday',
      'start_time': '08:00:00',
      'end_time': '08:45:00',
      'subject': subject,
      'teacher_first_name': 'Sipho',
      'teacher_last_name': 'Nkosi',
    },
  ],
};

/// `GET settings` answers by its `type`; the smoke flows only need the
/// app's settings.
Object settings(RequestOptions request) =>
    request.queryParameters['type'] == 'app_settings'
    ? ok(appSettings)
    : ok('');

Map<String, Object> learnerRoutes() => {
  'settings': settings,
  'v2/me/context': const FakeResponse.notFound(),
  'POST student/login': {
    ...ok(student(), message: 'User logged-in!'),
    'token': 'smoke-student-token',
  },
  'student/get-profile-data': ok(student()),
  'student/dashboard': ok(dashboard()),
};

/// A parent of Thandi and Sipho (the switcher shows from two children).
Map<String, Object?> parentOfTwo() => {
  ...parent(),
  'children': [student(), student(id: 12, firstName: 'Sipho')],
};

/// What GET /api/v2/me/notification-preferences answers (Phase 1 contract).
const List<Map<String, Object?>> notificationPreferences = [
  {
    'type': 'comms.notice',
    'label': 'New notice',
    'module': null,
    'channels': {
      'push': {'enabled': true, 'locked': false, 'reason': null},
      'email': {'enabled': true, 'locked': false, 'reason': null},
    },
  },
  {
    'type': 'attendance.absent',
    'label': 'Absence alert',
    'module': null,
    'channels': {
      'push': {
        'enabled': true,
        'locked': true,
        'reason': 'The school always tells you when your child is absent.',
      },
    },
  },
];

/// A parent of two, on a server with notification settings (Phase 1).
Map<String, Object> parentOfTwoRoutes() => {
  ...parentRoutes(),
  'POST parent/login': {
    ...ok(parentOfTwo(), message: 'User logged-in!'),
    'token': 'smoke-parent-token',
  },
  'parent/get-profile-data': ok(parentOfTwo()),
  'GET v2/me/notification-preferences': ok(notificationPreferences),
  'PUT v2/me/notification-preferences': ok(null),
  // Not deployed on this server: their entry points stay hidden.
  'v2/guardian/approvals': const FakeResponse.notFound(),
  'v2/me/avatar': const FakeResponse.notFound(),
};

Map<String, Object> parentRoutes() => {
  'settings': settings,
  'v2/me/context': const FakeResponse.notFound(),
  'POST parent/login': {
    ...ok(parent(), message: 'User logged-in!'),
    'token': 'smoke-parent-token',
  },
  'parent/policy-acceptance': ok({
    'navigateed': {'accepted': true},
    'school': {'required': false},
  }),
  'parent/get-profile-data': ok(parent()),
};
