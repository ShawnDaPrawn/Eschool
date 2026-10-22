import 'package:eschool/ui/screens/parentHomeScreen.dart';
import 'package:eschool_core/eschool_core.dart';
import 'package:eschool_notifications/eschool_notifications.dart';
import 'package:flutter/material.dart';
import 'package:flutter_test/flutter_test.dart';
import 'package:integration_test/integration_test.dart';

import 'support/fixtures.dart';
import 'support/smoke.dart';

void main() {
  IntegrationTestWidgetsFlutterBinding.ensureInitialized();

  testWidgets('a parent opens Notification settings and turns off email', (
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

    await tapWhenReady(tester, find.byIcon(Icons.settings));
    // Listed because the server answers the endpoint; Approvals, which it
    // does not have yet, is not.
    await pumpUntilFound(tester, find.text('Notification settings'));
    expect(api.requested('GET v2/guardian/approvals'), isTrue);
    expect(find.text('Approvals'), findsNothing);
    await tapWhenReady(tester, find.text('Notification settings'));
    await pumpUntilFound(tester, find.byType(NotificationSettingsScreen));
    expect(find.text('New notice'), findsOneWidget);
    // The mandatory notice shows its lock and why.
    expect(
      find.text('The school always tells you when your child is absent.'),
      findsOneWidget,
    );

    await tapWhenReady(
      tester,
      find.descendant(
        of: find.widgetWithText(SwitchListTile, 'Email'),
        matching: find.byType(Switch),
      ),
    );
    await tester.pump(const Duration(milliseconds: 500));

    final saved = api.requests.where(
      (request) =>
          request.method == 'PUT' &&
          request.uri.path.endsWith('v2/me/notification-preferences'),
    );
    expect(saved.single.data, {
      'type': 'comms.notice',
      'channel': 'email',
      'enabled': false,
    });
  });
}
