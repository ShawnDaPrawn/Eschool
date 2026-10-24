import 'package:eschool_teacher/data/models/term.dart';
import 'package:flutter_test/flutter_test.dart';

void main() {
  test('grade ranges use R and stay distinct from school-wide terms', () {
    final band = Term.fromJson({
      'id': 1,
      'name': 'Term 1',
      'grade_from': 0,
      'grade_to': 3,
    });
    expect(band.displayName, 'Term 1 (R–3)');
    final school = Term.fromJson({
      'id': 2,
      'name': 'Term 1',
      'grade_from': 0,
      'grade_to': 12,
    });
    expect(school.displayName, 'Term 1');
  });
}
