import 'package:eschool_api/eschool_api.dart';
import 'package:test/test.dart';

void main() {
  test('support dates and professional capability choices survive JSON', () {
    final json = <String, dynamic>{
      'audiences': ['professional', 'teacher'],
      'can_write': false,
      'today': '2026-02-10',
    };
    final access = SupportAccess.fromJson(json);
    expect(access.today, '2026-02-10');
    expect(access.canWrite, false);
    expect(access.audiences, ['professional', 'teacher']);
    expect(access.toJson(), json);
  });
  test('teacher projection has no clinical plan or history fields', () {
    final json = <String, dynamic>{
      'id': 2,
      'learner_id': 3,
      'learner_name': 'Synthetic learner',
      'revision': 1,
      'expires_on': '2026-03-10',
      'accommodations': 'Approved instructions',
    };
    final row = SupportTeacherDisclosure.fromJson(json);
    expect(row.expiresOn, '2026-03-10');
    expect(row.toJson(), json);
    expect(row.toJson().keys, isNot(contains('needs')));
    expect(row.toJson().keys, isNot(contains('reviews')));
  });
  test(
    'disclosure approval remains a required literal boolean on the wire',
    () {
      final json = <String, dynamic>{
        'operation_id': 'ca2bca07-b767-4b12-ac79-447508940778',
        'revision': 2,
        'reason': 'Explicit instructions',
        'user_id': 4,
        'expires_on': '2026-03-10',
        'accommodations': 'Approved instructions',
        'confirmation': true,
      };
      final input = SupportDiscloseInput.fromJson(json);
      expect(input.confirmation, true);
      expect(input.toJson(), json);
    },
  );
}
