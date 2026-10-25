import 'dart:convert';
import 'package:eschool_api/eschool_api.dart';
import 'package:test/test.dart';

void main() {
  const operation = '25791550-3b61-4ad0-ab4c-b453c2fdb01c';

  test(
    'seasonal group writes retain calendar dates without timezone expansion',
    () {
      final input = ActivityGroupWrite.fromJson({
        'operation_id': operation,
        'revision': 0,
        'session_year_id': 2,
        'parent_id': null,
        'name': 'Hockey',
        'discipline': 'Sport',
        'season': '2026',
        'starts_on': '2026-01-01',
        'ends_on': '2026-12-31',
        'gender': 'any',
        'minimum_grade': null,
        'maximum_grade': null,
        'minimum_age': null,
        'maximum_age': null,
        'active': true,
      });
      final output =
          jsonDecode(jsonEncode(input.toJson())) as Map<String, dynamic>;
      expect(output['starts_on'], '2026-01-01');
      expect(output['ends_on'], '2026-12-31');
      expect(output['operation_id'], operation);
      expect(output['revision'], 0);
    },
  );

  test('session attendance never invents missing learner entries', () {
    final input = ActivityAttendanceWrite.fromJson({
      'operation_id': operation,
      'revision': 3,
      'reason': 'Coach correction',
      'entries': [
        {'learner_id': 7, 'status': 'late', 'revision': 1},
      ],
    });
    final output =
        jsonDecode(jsonEncode(input.toJson())) as Map<String, dynamic>;
    expect(output['entries'], [
      {'learner_id': 7, 'status': 'late', 'revision': 1},
    ]);
    expect(output['reason'], 'Coach correction');
  });

  test(
    'activity audience selection and snapshot retain the exact supported kind',
    () {
      final data = {
        'kind': 'activity_group',
        'session_year_id': 2,
        'target_id': 9,
        'parents': true,
      };
      expect(CommunicationAudienceSelection.fromJson(data).toJson(), data);
      expect(CommunicationAudienceSnapshot.fromJson(data).toJson(), data);
      final scope = CommunicationScope.fromJson({
        'kind': 'activity_group',
        'target_id': 9,
        'name': 'Hockey',
      });
      expect(scope.toJson()['kind'], 'activity_group');
    },
  );

  test(
    'cancelling an existing allocation preserves explicit false on the wire',
    () {
      final input = ActivityStaffWrite.fromJson({
        'operation_id': operation,
        'revision': 1,
        'user_id': 12,
        'role': 'facilitator',
        'cascade': false,
        'active': false,
        'starts_on': '2026-05-01',
        'ends_on': '2026-12-31',
        'reason': 'Coach cancelled',
      });
      final output = input.toJson();
      expect(output['active'], false);
      expect(output['starts_on'], '2026-05-01');
    },
  );
}
