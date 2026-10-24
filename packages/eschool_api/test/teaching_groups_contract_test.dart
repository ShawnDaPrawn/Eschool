import 'package:eschool_api/eschool_api.dart';
import 'package:test/test.dart';

void main() {
  test(
    'dated assignment keeps date-only values and exact effective context',
    () {
      final row = AcademicAssignmentWrite.fromJson({
        'starts_on': '2026-01-14',
        'ends_on': '2026-12-04',
        'reason': 'Subject allocation',
        'teacher_id': 8,
        'revision': 2,
      });
      expect(row.toJson()['starts_on'], '2026-01-14');
      expect(row.toJson()['ends_on'], '2026-12-04');
      expect(row.toJson()['teacher_id'], 8);
      expect(row.toJson()['revision'], 2);
    },
  );

  test('a mixed-homeroom group response preserves its nullable homeroom', () {
    final group = TeachingGroup.fromJson({
      'id': 1,
      'name': 'Grade 7 maths',
      'subject_id': 3,
      'grade_level_id': 8,
      'session_year_id': 1,
      'class_section_id': null,
      'active': true,
      'revision': 1,
    });
    expect(group.classSectionId, isNull);
    expect(group.gradeLevelId, 8);
  });

  test('principal and learner assignments retain distinct optional fields', () {
    final principal = AcademicAssignment.fromJson({
      'id': 3,
      'starts_on': '2026-01-01',
      'ends_on': '2026-12-31',
      'reason': 'Appointment',
      'revision': 1,
      'display_name': 'Principal',
      'user_id': 9,
      'session_year_id': 1,
      'kind': 'principal',
      'grade_level_id': null,
    });
    final member = AcademicAssignment.fromJson({
      'id': 4,
      'starts_on': '2026-02-01',
      'ends_on': '2026-12-31',
      'reason': 'Joined',
      'revision': 1,
      'display_name': 'Learner',
      'learner_id': 12,
      'teaching_group_id': 2,
      'mark_rule': 'catch_up',
    });
    expect(principal.gradeLevelId, isNull);
    expect(member.learnerId, 12);
    expect(member.teacherId, isNull);
  });
}
