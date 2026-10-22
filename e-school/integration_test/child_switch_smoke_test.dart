import 'package:eschool/ui/screens/childDetailsScreen.dart';
import 'package:eschool/ui/screens/parentHomeScreen.dart';
import 'package:eschool_core/eschool_core.dart';
import 'package:eschool_session/eschool_session.dart';
import 'package:flutter_test/flutter_test.dart';
import 'package:integration_test/integration_test.dart';

import 'support/fixtures.dart';
import 'support/smoke.dart';

void main() {
  IntegrationTestWidgetsFlutterBinding.ensureInitialized();

  testWidgets('a parent of two switches child once and the screen follows', (
    tester,
  ) async {
    final api = await startSmokeApp(tester, parentOfTwoRoutes());

    await tapWhenReady(tester, find.text('Login as Parent'));
    await pumpUntilFound(tester, find.text('Email'));
    await enterField(tester, 0, parentEmail);
    await enterField(tester, 1, password);
    await tapWhenReady(
      tester,
      find.widgetWithText(CustomRoundedButton, 'Sign in'),
    );
    await pumpUntilFound(tester, find.byType(ParentHomeScreen));

    // Home lists both children; picking Thandi shows her screens.
    await tapWhenReady(tester, find.textContaining('Thandi'));
    await pumpUntilFound(tester, find.byType(ChildDetailsScreen));
    expect(api.requested('GET parent/subjects'), isTrue);

    // The switcher, not home, changes child, and the open screen follows:
    // a bar above the screen on a phone, a list beside it in a wide window.
    if (find.byType(ChildSwitcherBar).evaluate().isNotEmpty) {
      await tapWhenReady(tester, find.byType(ChildSwitcherBar));
      await pumpUntilFound(tester, find.text('Choose a child'));
      await tapWhenReady(tester, find.text('Sipho Mokoena').last);
    } else {
      expect(find.byType(ChildSwitcherRail), findsOneWidget);
      await tapWhenReady(
        tester,
        find.descendant(
          of: find.byType(ChildSwitcherRail),
          matching: find.text('Sipho Mokoena'),
        ),
      );
    }
    await pumpUntilFound(
      tester,
      find.descendant(
        of: find.byType(ChildDetailsScreen),
        matching: find.text('Sipho Mokoena'),
      ),
    );
    final requests = api.requests
        .where((request) => request.uri.path.endsWith('parent/subjects'))
        .map((request) => request.queryParameters['child_id'])
        .toList();
    expect(requests.last, 12);
  });
}
