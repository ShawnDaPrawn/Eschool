import 'package:eschool_api/eschool_api.dart';
import 'package:test/test.dart';

void main() {
  test('older facilitator flags preserve omission of new capabilities', () {
    final data = {'groups': true, 'messages': true, 'counselling': false};
    final flags = FacilitatorFeatures.fromJson(data);
    expect(flags.supportPlans, isNull);
    expect(flags.learnerNotes, isNull);
    expect(flags.toJson(), data);
    final current = FacilitatorFeatures.fromJson({
      ...data,
      'support_plans': true,
      'learner_notes': false,
    });
    expect(current.supportPlans, isTrue);
    expect(current.learnerNotes, isFalse);
    expect(current.toJson()['learner_notes'], isFalse);
  });

  test(
    'private note request and encrypted-receipt response shapes round trip',
    () {
      final body = List.filled(20000, 'é').join();
      final request = LearnerNoteCreate.fromJson({
        'operation_id': '4abe121c-21cd-4429-a4c9-9cf1a54fbfee',
        'session_year_id': 7,
        'subject_id': 3,
        'visibility': 'counselling',
        'body': body,
      });
      expect(request.body, body);
      expect(request.toJson()['visibility'], 'counselling');
      final detail = LearnerNoteDetail.fromJson({
        'id': 9,
        'learner_id': 4,
        'subject_id': null,
        'session_year_id': 7,
        'author_id': 2,
        'author_name': 'Synthetic staff',
        'visibility': 'counselling',
        'revision': 2,
        'archived': false,
        'updated_at': '2026-02-10T10:00:00Z',
        'body': body,
      });
      expect(detail.subjectId, isNull);
      expect(detail.body, body);
      expect(detail.revision, 2);
    },
  );

  test('unknown evidence remains nullable and calendar dates remain dates', () {
    final band = ProgressStaffBand.fromJson({
      'audience': 'staff',
      'learner_id': 4,
      'subject_id': 3,
      'session_year_id': 7,
      'term_id': 5,
      'snapshot_id': 8,
      'provisional': true,
      'band': 'insufficient_evidence',
      'percentage_bp': null,
      'coverage_bp': 0,
      'alerts_eligible': false,
      'settings_revision': 0,
    });
    expect(band.percentageBp, isNull);
    expect(band.alertsEligible, isFalse);
    final assignment = LearnerNoteAssignmentWrite.fromJson({
      'operation_id': '4abe121c-21cd-4429-a4c9-9cf1a54fbfee',
      'expected_revision': 0,
      'starts_on': '2026-02-01',
      'ends_on': '2026-02-28',
    });
    expect(assignment.toJson()['starts_on'], '2026-02-01');
  });
  test('assignment revocation preserves legacy omission and exact intent', () {
    final older = <String, dynamic>{
      'id': 5,
      'learner_id': 4,
      'staff_user_id': 2,
      'staff_name': 'Synthetic clinician',
      'starts_on': '2026-10-01',
      'ends_on': '2026-10-31',
      'revision': 1,
    };
    final grant = LearnerNoteAssignment.fromJson(older);
    expect(grant.revoked, isNull);
    expect(grant.toJson(), older);
    final revoked = LearnerNoteAssignment.fromJson({...older, 'revoked': true});
    expect(revoked.revoked, isTrue);
    expect(revoked.toJson()['starts_on'], older['starts_on']);
    expect(revoked.toJson()['revoked'], isTrue);
    final request = LearnerNoteAssignmentRevoke.fromJson({
      'operation_id': '4abe121c-21cd-4429-a4c9-9cf1a54fbfee',
      'expected_revision': 1,
    });
    expect(request.toJson(), {
      'operation_id': '4abe121c-21cd-4429-a4c9-9cf1a54fbfee',
      'expected_revision': 1,
    });
  });
}
