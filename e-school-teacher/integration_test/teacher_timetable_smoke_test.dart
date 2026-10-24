import 'package:eschool_academics/eschool_academics.dart';
import 'package:eschool_academics/testing.dart';
import 'package:eschool_core/eschool_core.dart';
import 'package:eschool_teacher/ui/screens/home/homeScreen.dart';
import 'package:flutter/material.dart';
import 'package:flutter_test/flutter_test.dart';
import 'package:integration_test/integration_test.dart';

import 'support/fixtures.dart';
import 'support/smoke.dart';

void main() {
  IntegrationTestWidgetsFlutterBinding.ensureInitialized();
  testWidgets('teacher opens the week from the next-period room card', (
    tester,
  ) async {
    final api = await startSmokeApp(tester, {
      ...teacherRoutes(),
      'GET v2/me/context': ok({
        ...academicContextFixture(),
        'feature_flags': {'academic_timetable': true},
      }),
      'GET v2/academics/timetable/next': ok({
        'lesson': timetableLessonFixture(),
      }),
      'GET v2/academics/timetable/week': ok(timetableWeekFixture()),
    });
    await pumpUntilFound(tester, find.text('Email or handle'));
    await enterField(tester, 0, teacherEmail);
    await enterField(tester, 1, password);
    await tapWhenReady(
      tester,
      find.widgetWithText(CustomRoundedButton, 'Sign in'),
    );
    await pumpUntilFound(tester, find.byType(HomeScreen));
    await pumpUntilFound(tester, find.text('Room 101 · Main'));
    expect(find.text('Sipho Nkosi'), findsWidgets);
    await tapWhenReady(tester, find.text('View week'));
    await pumpUntilFound(tester, find.byType(TimetableScreen));
    await pumpUntilFound(tester, find.text('Room 101'));
    expect(find.text('Place lesson'), findsNothing);
    expect(api.requested('GET v2/academics/timetable/configuration'), isFalse);
    final request = api.requests.lastWhere(
      (r) => r.uri.path.endsWith('/timetable/week'),
    );
    expect(request.headers['X-School-Id'], '1');
    expect(request.queryParameters, isEmpty);
    expect(tester.takeException(), isNull);
  });
}
