import 'family_exam_coach_smoke_test.dart' as exam;
import 'family_tutor_checkpoints_smoke_test.dart' as checkpoints;

import 'package:integration_test/integration_test.dart';
import 'package:flutter_test/flutter_test.dart';

void main() {
  exam.main();
  checkpoints.main();
  tearDownAll(() {
    final binding = IntegrationTestWidgetsFlutterBinding.instance;
    binding.reportData = {
      'test_results': binding.results.map(
        (name, result) =>
            MapEntry(name, result == 'success' ? 'passed' : 'failed'),
      ),
    };
  });
}
