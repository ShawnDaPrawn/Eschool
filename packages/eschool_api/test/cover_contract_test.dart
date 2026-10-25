import 'package:eschool_api/eschool_api.dart';
import 'package:test/test.dart';

void main() {
  test('calendar dates and UUID remain exact on a retried cover request', () {
    final input = CoverCreateInput.fromJson({
      'operation_id': 'c46bdab7-016c-4e80-a891-16605a74071d',
      'session_year_id': 7,
      'teacher_id': 12,
      'starts_on': '2026-02-10',
      'ends_on': '2026-02-17',
      'reason': null,
    });
    expect(input.toJson()['starts_on'], '2026-02-10');
    expect(input.toJson()['ends_on'], '2026-02-17');
    expect(
      input.toJson()['operation_id'],
      'c46bdab7-016c-4e80-a891-16605a74071d',
    );
  });
  test('an open occurrence decodes without an assigned substitute', () {
    final data = <String, dynamic>{
      'id': 8,
      'absence_id': 3,
      'revision': 1,
      'state': 'open',
      'on_date': '2026-02-10',
      'substitute_teacher_id': null,
      'substitute_name': null,
      'source': {
        'lesson_id': 5,
        'lesson_revision': 1,
        'session_year_id': 7,
        'teacher_id': 12,
        'teacher_name': 'Teacher A',
        'group_id': 9,
        'homeroom_id': null,
        'title': 'Maths',
        'subject_name': null,
        'room_id': 4,
        'room_name': 'Room 4',
        'period_id': 1,
        'starts_at': '08:00',
        'ends_at': '08:45',
        'on_date': '2026-02-10',
      },
    };
    final occurrence = CoverOccurrence.fromJson(data);
    expect(occurrence.substituteTeacherId, isNull);
    expect(occurrence.substituteName, isNull);
    expect(occurrence.source_.homeroomId, isNull);
    expect(occurrence.toJson()['on_date'], '2026-02-10');
    expect(occurrence.state, CoverOccurrenceStateEnum.open);
    data['state'] = 'needs_review';
    expect(
      CoverOccurrence.fromJson(data).state,
      CoverOccurrenceStateEnum.needsReview,
    );
  });
}
