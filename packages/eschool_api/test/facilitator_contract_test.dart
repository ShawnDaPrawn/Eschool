import 'package:eschool_api/eschool_api.dart';
import 'package:test/test.dart';

void main() {
  final profile = {
    'user_id': 12,
    'school_id': 7,
    'name': 'Synthetic coach',
    'roles': ['Facilitator'],
    'schools': [
      {'id': 7, 'name': 'Synthetic School', 'slug': 'synthetic-school'},
    ],
    'features': {'groups': true, 'messages': true, 'counselling': false},
  };
  test(
    'cookie sign-in decodes a nullable bearer without losing scoped profile',
    () {
      final json = {
        'error': false,
        'message': 'Signed in.',
        'code': null,
        'data': {
          'authenticated': true,
          'token': null,
          'profile': profile,
          'password_change_required': false,
          'documents': [],
        },
      };
      final reply = FacilitatorSignInResponse.fromJson(json);
      expect(reply.data.token, isNull);
      expect(reply.data.profile!.schoolId, 7);
      expect(reply.data.profile!.features.counselling, isFalse);
      expect(reply.toJson(), json);
    },
  );
  test(
    'setup reply retains policy versions and cannot imply an authenticated profile',
    () {
      final json = {
        'error': true,
        'message': 'Complete setup.',
        'code': 'FACILITATOR_SETUP_REQUIRED',
        'data': {
          'authenticated': false,
          'token': null,
          'profile': null,
          'password_change_required': true,
          'documents': [
            {
              'document': 'school',
              'version': 'a' * 64,
              'title': 'School policy',
              'text': 'Synthetic readable policy.',
            },
          ],
        },
      };
      final reply = FacilitatorSignInResponse.fromJson(json);
      expect(reply.data.profile, isNull);
      expect(reply.data.authenticated, isFalse);
      expect(reply.data.documents.single.version, 'a' * 64);
      expect(reply.toJson(), json);
    },
  );
  test('sign-out has a literal nullable payload and code', () {
    final json = {
      'error': false,
      'message': 'Signed out.',
      'data': null,
      'code': null,
    };
    expect(FacilitatorSignOutResponse.fromJson(json).toJson(), json);
  });
}
