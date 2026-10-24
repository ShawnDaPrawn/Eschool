import 'dart:convert';
import 'dart:io';

import 'package:eschool_teacher/data/models/studentResult.dart';
import 'package:flutter_test/flutter_test.dart';

void main() {
  test(
    'existing whole-number result models decode an exact academic projection',
    () {
      final fixture = jsonDecode(
        File('test/fixtures/academic_projection.json').readAsStringSync(),
      ) as Map<String, dynamic>;
      final marks = Marks.fromJson(fixture['marks'] as Map<String, dynamic>);
      final result = ResultData.fromJson(
        fixture['result'] as Map<String, dynamic>,
      );
      expect(marks.obtainedMarks, 7505);
      expect(marks.totalMarks, 10000);
      expect(result.obtainedMarks, 7505);
      expect(result.totalMarks, 10000);
      expect(result.percentage, 75.05);
      expect(result.grade, '6');
    },
  );
}
