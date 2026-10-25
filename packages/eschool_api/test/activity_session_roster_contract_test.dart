import 'package:eschool_api/eschool_api.dart';
import 'package:test/test.dart';

void main() {
  test('old roster JSON omits the optional session projection', () {
    final old = {
      'memberships': <Object>[],
      'staff': <Object>[],
      'attendance': <Object>[],
    };
    final result = ActivityGroupRoster.fromJson(old);
    expect(result.sessionRoster, isNull);
    expect(result.toJson(), old);
  });
  test(
    'session projection retains null status and exact correction revisions',
    () {
      final projection = {
        'session_id': 9,
        'group_id': 7,
        'session_revision': 4,
        'learners': [
          {
            'learner_id': 17,
            'learner_name': 'Synthetic current learner',
            'status': null,
            'revision': 0,
          },
          {
            'learner_id': 19,
            'learner_name': 'Synthetic former learner',
            'status': 'late',
            'revision': 3,
          },
        ],
      };
      final source = {
        'memberships': <Object>[],
        'staff': <Object>[],
        'attendance': <Object>[],
        'session_roster': projection,
      };
      final result = ActivityGroupRoster.fromJson(source);
      expect(result.sessionRoster!.learners.first.status, isNull);
      expect(result.sessionRoster!.learners.last.revision, 3);
      expect(result.toJson(), source);
    },
  );
}
