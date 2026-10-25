import 'package:eschool_api/eschool_api.dart';
import 'package:test/test.dart';

void main() {
  test('transfer calendar days serialize without a midnight timestamp', () {
    final input = TransferPrepareInput(
      operationId: 'd3b4c78f-8827-4671-8a52-1f57b813fbf1',
      learnerId: 12,
      guardianId: 41,
      toSchoolId: 2,
      externalSchool: null,
      lastAttendedOn: '2026-02-28',
      startsOn: '2026-03-01',
      reportIds: [15],
      documentIds: [],
    );
    expect(input.toJson()['last_attended_on'], '2026-02-28');
    expect(input.toJson()['starts_on'], '2026-03-01');
    expect(input.toJson()['report_ids'], [15]);
  });

  test(
    'guardian approval carries the exact package and current consent version',
    () {
      final input = TransferConsentInput(
        operationId: 'd3b4c78f-8827-4671-8a52-1f57b813fbf1',
        revision: 3,
        fingerprint: 'a' * 64,
        version: 'school-transfer-v1',
        confirmation: true,
      );
      expect(input.toJson(), containsPair('fingerprint', 'a' * 64));
      expect(input.toJson(), containsPair('revision', 3));
      expect(input.toJson(), containsPair('version', 'school-transfer-v1'));
      expect(input.toJson(), containsPair('confirmation', true));
    },
  );

  test(
    'received history retains unknown marks and separate guarded capabilities',
    () {
      final detail = TransferDetail.fromJson({
        'id': 7,
        'public_id': 'd3b4c78f-8827-4671-8a52-1f57b813fbf1',
        'state': 'accepted',
        'revision': 4,
        'fingerprint': 'b' * 64,
        'learner_name': 'Synthetic learner',
        'student_number': 'Synthetic identity',
        'guardian_name': 'Synthetic guardian',
        'from_school_name': 'Sending demo',
        'to_school_name': 'Receiving demo',
        'last_attended_on': '2026-02-28',
        'starts_on': '2026-03-01',
        'expires_at': null,
        'export_only': false,
        'report_count': 0,
        'document_count': 0,
        'mark_count': 1,
        'attendance_count': 0,
        'consent_version': 'school-transfer-v1',
        'reports': [],
        'documents': [],
        'released_marks': [
          {
            'release_id': 5,
            'release_version': 1,
            'subject_name': 'Demo mathematics',
            'grade_level_id': 8,
            'session_year_id': 2,
            'term_id': null,
            'mode': 'final',
            'percentage_bp': null,
            'rating_level': null,
            'coverage_bp': 0,
            'fingerprint': 'c' * 64,
            'released_at': '2026-02-10T08:00:00Z',
          },
        ],
        'attendance': [],
        'can_consent': false,
        'can_withdraw': false,
        'can_release': false,
        'can_cancel': false,
        'can_accept': false,
        'can_download': true,
      });
      expect(detail.releasedMarks.single.percentageBp, isNull);
      expect(detail.releasedMarks.single.coverageBp, 0);
      expect(detail.canConsent, isFalse);
      expect(detail.canAccept, isFalse);
      expect(detail.canDownload, isTrue);
      expect(detail.startsOn, '2026-03-01');
      expect(detail.expiresAt, isNull);
    },
  );
}
