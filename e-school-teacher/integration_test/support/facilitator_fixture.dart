import 'package:eschool_core/testing.dart';

Map<String, dynamic> staffProfileFixture({int user = 12, int school = 1}) => {
  'user_id': user,
  'school_id': school,
  'name': 'Synthetic Coach $user',
  'roles': ['Facilitator'],
  'schools': [
    {'id': 1, 'name': 'Jacaranda School', 'slug': 'jacaranda'},
    {'id': 2, 'name': 'Baobab School', 'slug': 'baobab'},
  ],
  'features': {'groups': true, 'messages': true, 'counselling': false},
};
Map<String, dynamic> staffReplyFixture({
  int user = 12,
  int school = 1,
  bool cookie = false,
}) => {
  'error': false,
  'message': 'Signed in.',
  'data': {
    'authenticated': true,
    'token': cookie ? null : '$user|synthetic-facilitator-token',
    'profile': staffProfileFixture(user: user, school: school),
    'password_change_required': false,
    'documents': [],
  },
  'code': null,
};
const staffCredentials = {
  'school': 'jacaranda',
  'email': 'coach@example.test',
  'password': 'SyntheticStaff2026',
};
Map<String, dynamic> staffSetupFixture() => {
  'error': true,
  'message': 'Complete sign-in setup.',
  'data': {
    'authenticated': false,
    'token': null,
    'profile': null,
    'password_change_required': true,
    'documents': [
      {
        'document': 'navigateed',
        'version': 'a' * 64,
        'title': 'NavigateEd',
        'text': 'Read this synthetic policy before accepting.',
      },
    ],
  },
  'code': 'FACILITATOR_SETUP_REQUIRED',
};
FakeApi staffApi({Object? signIn, Object? session, Object? logout}) => FakeApi(
  routes: {
    'POST v2/facilitator/sign-in': signIn ?? staffReplyFixture(),
    'GET v2/facilitator/session':
        session ?? {'error': false, 'data': staffProfileFixture()},
    'POST v2/facilitator/sign-out': logout ?? {'error': false, 'data': null},
    'GET v2/activities/groups': {
      'error': false,
      'data': {'items': [], 'page': 1, 'last_page': 1, 'total': 0},
    },
  },
);
