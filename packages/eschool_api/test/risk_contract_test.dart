import 'package:eschool_api/eschool_api.dart';
import 'package:test/test.dart';

void main() {
  test('unknown evidence stays distinct from a measured zero', () {
    final missing = RiskSignal.fromJson({
      'source': 'wellbeing',
      'score': null,
      'signal_count': null,
      'reason_code': 'source_unavailable',
      'grade_level_id': 7,
      'as_of': '2026-02-10',
    });
    expect(missing.score, isNull);
    expect(missing.signalCount, isNull);
    expect(missing.toJson()['as_of'], '2026-02-10');
    final zero = RiskSignal.fromJson({
      'source': 'academic',
      'score': 0,
      'signal_count': 0,
      'reason_code': 'no_observed_signal',
      'grade_level_id': 7,
      'as_of': '2026-02-10',
    });
    expect(zero.score, 0);
    expect(zero.signalCount, 0);
  });

  test(
    'owned encrypted source shapes preserve calendar dates and private text',
    () {
      final action = List.filled(20000, 'é').join();
      final data = <String, dynamic>{
        'id': 5,
        'learner_id': 4,
        'session_year_id': 3,
        'grade_level_id': 7,
        'owner_authority': 'professional',
        'owner_id': 2,
        'started_on': '2026-02-10',
        'review_on': '2026-02-20',
        'state': 'active',
        'revision': 1,
        'action': action,
        'outcome': null,
      };
      final detail = InterventionDetail.fromJson(data);
      expect(detail.action, action);
      expect(detail.outcome, isNull);
      expect(detail.toJson()['started_on'], '2026-02-10');
      expect(detail.toJson()['review_on'], '2026-02-20');
      final retry = InterventionUpdate.fromJson({
        'operation_id': '4abe121c-21cd-4429-a4c9-9cf1a54fbfee',
        'expected_revision': 1,
        'action': action,
        'review_on': '2026-02-20',
        'state': 'active',
        'outcome': null,
      });
      expect(
        retry.toJson()['operation_id'],
        '4abe121c-21cd-4429-a4c9-9cf1a54fbfee',
      );
      expect(retry.toJson()['review_on'], '2026-02-20');
    },
  );

  test('metadata never invents a right to read a former professional body', () {
    final row = InterventionQueueItem.fromJson({
      'id': 5,
      'learner_id': 4,
      'owner_id': 2,
      'grade_level_id': 7,
      'review_on': '2026-02-20',
      'state': 'active',
      'revision': 2,
      'can_read_body': false,
    });
    expect(row.canReadBody, isFalse);
    expect(row.toJson().containsKey('action'), isFalse);
    final grant = RiskAssignmentRevoke.fromJson({
      'operation_id': '4abe121c-21cd-4429-a4c9-9cf1a54fbfee',
      'session_year_id': 3,
      'expected_revision': 1,
    });
    expect(grant.toJson()['expected_revision'], 1);
  });
}
