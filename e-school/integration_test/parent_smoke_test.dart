import 'package:eschool/ui/screens/childDetailsScreen.dart';
import 'package:eschool/ui/screens/parentHomeScreen.dart';
import 'package:eschool_core/eschool_core.dart';
import 'package:flutter_test/flutter_test.dart';
import 'package:integration_test/integration_test.dart';

import 'support/fixtures.dart';
import 'support/smoke.dart';

void main() {
  IntegrationTestWidgetsFlutterBinding.ensureInitialized();

  testWidgets('a parent signs in, lands on home and opens their child', (
    tester,
  ) async {
    final api = await startSmokeApp(tester, parentRoutes());

    final Finder asParent = find.text('Login as Parent');
    await pumpUntilFound(tester, asParent);
    await tester.tap(asParent);
    await pumpUntilFound(tester, find.text('Email'));

    await enterField(tester, 0, parentEmail);
    await enterField(tester, 1, password);
    await tester.tap(find.widgetWithText(CustomRoundedButton, 'Sign in'));

    await pumpUntilFound(tester, find.byType(ParentHomeScreen));
    expect(api.requested('POST parent/login'), isTrue);
    expect(api.requested('GET parent/policy-acceptance'), isTrue);

    // Wait until the child card can take a tap (see the learner flow).
    final childCard = find.textContaining('Thandi').hitTestable();
    await pumpUntilFound(tester, childCard);
    await tester.tap(childCard.first);
    await pumpUntilFound(tester, find.byType(ChildDetailsScreen));
  });
}
