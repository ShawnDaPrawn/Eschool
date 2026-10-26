import 'package:dio/dio.dart';
import 'package:eschool_academics/testing.dart';
import 'package:eschool_session/eschool_session.dart';
import 'package:eschool_teacher/app/app.dart';
import 'package:eschool_teacher/facilitator/facilitator_repository.dart';
import 'package:flutter/material.dart';
import 'package:flutter_test/flutter_test.dart';
import 'package:hive/hive.dart';
import 'package:integration_test/integration_test.dart';

import 'support/exam_journey.dart';
import 'support/smoke.dart' show pumpUntilFound, tapWhenReady;
import 'support/wellbeing_fixture.dart';

class _ExamStaff extends ClinicalStaffFixture {
  bool enabled = true;
  @override
  Map<String, dynamic> profile() => {
    ...super.profile(),
    'roles': ['Facilitator', 'Teacher'],
    'features': {
      'exam_coach': enabled,
      'groups': false,
      'messages': false,
      'counselling': false,
    },
  };
}

void main() {
  IntegrationTestWidgetsFlutterBinding.ensureInitialized();
  testWidgets(
    'Fac Teacher completes exam library/review then loses the fresh feature without legacy auth',
    (tester) async {
      final staff = _ExamStaff();
      final fixture = ExamCoachSmokeFixture(reader: 12);
      staff.api.routes.addAll(fixture.routes(() => staff.api));
      final repository = FacilitatorRepository(
        mode: AuthMode.bearer,
        createDio: () => Dio()..httpClientAdapter = staff.api,
      );
      await initializeApp(facilitatorRepository: repository);
      await pumpUntilFound(tester, find.text('Facilitator sign-in'));
      Finder field(String label) => find.byWidgetPredicate(
        (w) => w is TextField && w.decoration?.labelText == label,
      );
      await tester.enterText(field('School code'), 'jacaranda');
      await tester.enterText(field('Email or handle'), 'teacher@example.test');
      await tester.enterText(field('Password'), 'SyntheticStaff2026');
      await tapWhenReady(tester, find.text('Sign in'));
      await tapWhenReady(tester, find.text('Exam Coach'));
      await runExamTeacher(tester, fixture);
      staff.enabled = false;
      fixture.permitted = false;
      await tester.runAsync(repository.refresh);
      await tester.pumpAndSettle();
      expect(find.text('Explain the two equal parts.'), findsNothing);
      expect(find.text('Exam Coach'), findsNothing);
      expect(Hive.isBoxOpen('authBox'), false);
      final requests = staff.api.requests.where(
        (r) => r.uri.path.contains('/exam-coach/'),
      );
      expect(requests, isNotEmpty);
      expect(
        requests.every(
          (r) =>
              r.headers['X-School-Id'] == '1' &&
              r.headers['X-Account-Id'] == '12',
        ),
        true,
      );
    },
  );
}
