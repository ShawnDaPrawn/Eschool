import 'package:eschool_core/eschool_core.dart';
import 'package:eschool_teacher/ui/screens/home/homeScreen.dart';
import 'package:eschool_teacher/ui/screens/home/tabs/timeTableContainer.dart';
import 'package:flutter/widgets.dart';
import 'package:flutter_test/flutter_test.dart';
import 'package:integration_test/integration_test.dart';

import 'support/fixtures.dart';
import 'support/smoke.dart';

void main() {
  IntegrationTestWidgetsFlutterBinding.ensureInitialized();

  testWidgets('a teacher signs in, lands on home and opens the timetable', (
    tester,
  ) async {
    final api = await startSmokeApp(tester, teacherRoutes());

    await pumpUntilFound(tester, find.text('Email'));
    await enterField(tester, 0, teacherEmail);
    await enterField(tester, 1, password);
    await tester.tap(find.widgetWithText(CustomRoundedButton, 'Sign in'));

    await pumpUntilFound(tester, find.byType(HomeScreen));
    expect(api.requested('POST teacher/login'), isTrue);
    expect(api.requested('GET teacher/policy-acceptance'), isTrue);
    expect(api.requested('GET teacher/dashboard'), isTrue);

    // The bottom bar on a phone, the rail on wider screens: both say it.
    // Wait until it can take a tap: on a slow emulator the bar is still
    // coming in when HomeScreen first appears.
    final schedule = find.text('Schedule').hitTestable();
    await pumpUntilFound(tester, schedule);
    await tester.tap(schedule.first);
    await pumpUntilFound(tester, find.byType(TimeTableContainer));
    final IndexedStack tabs = tester.widget(
      find
          .descendant(
            of: find.byType(HomeScreen),
            matching: find.byType(IndexedStack),
          )
          .first,
    );
    expect(tabs.index, 1);
    expect(api.requested('GET teacher/teacher_timetable'), isTrue);
  });
}
