import 'package:eschool_api/eschool_api.dart';
import 'package:test/test.dart';

void main() {
  test('clearing a reference serializes explicit null', () {
    expect(SchoolSubjectStandardWrite(standardId: null).toJson(), {
      'standard_id': null,
    });
  });

  test(
    'school subject mapping accepts no reference and preserves nullable code',
    () {
      final subject = SchoolSubjectStandard.fromJson({
        'subject_id': 4,
        'name': 'Maths',
        'code': null,
        'type': 'Theory',
        'medium_id': 2,
        'standard_id': null,
        'standard': null,
      });
      expect(subject.standard, isNull);
      expect(subject.code, isNull);
    },
  );

  test('reference components remain exact integer weights', () {
    final standard = SubjectStandard.fromJson({
      'id': 1,
      'framework': 'CAPS',
      'code': 'TEST-7',
      'name': 'Mathematics',
      'phase': 'Senior',
      'grade_from': 7,
      'grade_to': 9,
      'version': 'synthetic-v1',
      'source_url': 'https://example.test/standard',
      'effective_on': '2026-01-01',
      'component_weights': {'sba': 6000, 'exam': 4000},
    });
    expect(standard.componentWeights, {'sba': 6000, 'exam': 4000});
    expect(standard.gradeFrom, 7);
  });
}
