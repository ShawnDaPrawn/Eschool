import 'package:eschool_api/eschool_api.dart';
import 'package:test/test.dart';

void main() {
  test('template application defaults to a non-mutating preview', () {
    final request = TermTemplateApplyWrite(sessionYearId: 12);
    expect(request.toJson(), {'session_year_id': 12, 'apply': false});
  });

  test(
    'nullable calendar dates can be explicitly cleared by the generated SDK',
    () {
      final request = SchoolTermWrite(
        sessionYearId: 12,
        name: 'Term 1',
        startsOn: '2027-01-14',
        endsOn: '2027-03-20',
        marksLockOn: null,
        reportsOn: null,
      );
      expect(request.toJson().containsKey('marks_lock_on'), isTrue);
      expect(request.toJson()['marks_lock_on'], isNull);
      expect(request.toJson().containsKey('reports_on'), isTrue);
      expect(request.toJson()['reports_on'], isNull);
      expect(request.toJson()['starts_on'], '2027-01-14');
    },
  );

  test('a retained irregular calendar is readable and marked for review', () {
    final term = SchoolTerm.fromJson({
      'id': 1,
      'session_year_id': 12,
      'name': 'Imported calendar',
      'starts_on': null,
      'ends_on': null,
      'grade_from': 0,
      'grade_to': 12,
      'marks_lock_on': null,
      'reports_on': null,
      'current': false,
      'needs_review': true,
    });
    expect(term.startsOn, isNull);
    expect(term.needsReview, isTrue);
    expect(term.gradeFrom, 0);
  });
}
