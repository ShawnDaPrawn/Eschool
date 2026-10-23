import 'package:dio/dio.dart';
import 'package:eschool/ui/screens/parentHomeScreen.dart';
import 'package:eschool_core/eschool_core.dart';
import 'package:eschool_people/eschool_people.dart';
import 'package:flutter/material.dart';
import 'package:flutter_test/flutter_test.dart';
import 'package:integration_test/integration_test.dart';

import 'support/fixtures.dart';
import 'support/smoke.dart';

void main() {
  IntegrationTestWidgetsFlutterBinding.ensureInitialized();
  testWidgets('guardian opens handle requests and approves the proposed name', (
    tester,
  ) async {
    final state = {'handle': null, 'proposal': null, 'can_change_after': null};
    var pending = true;
    final api = await startSmokeApp(tester, {
      ...parentOfTwoRoutes(),
      'GET v2/me/handle': ok(state),
      'GET v2/guardian/handle-proposals': (RequestOptions _) => ok(
        pending
            ? [
                {
                  'id': 1,
                  'handle': 'orbit',
                  'expires_at': '2026-12-01T12:00:00Z',
                  'learner': {'id': 21, 'name': 'Asha'},
                },
              ]
            : [],
      ),
      'POST v2/guardian/handle-proposals/1': (RequestOptions _) {
        pending = false;
        return ok({...state, 'handle': 'orbit'});
      },
    });
    await tapWhenReady(tester, find.text('Login as Parent'));
    await pumpUntilFound(tester, find.text('Email or handle'));
    await enterField(tester, 0, parentEmail);
    await enterField(tester, 1, password);
    await tapWhenReady(
      tester,
      find.widgetWithText(CustomRoundedButton, 'Sign in'),
    );
    await pumpUntilFound(tester, find.byType(ParentHomeScreen));
    await tapWhenReady(tester, find.byIcon(Icons.settings));
    await tapWhenReady(tester, find.text('Handle and requests'));
    await pumpUntilFound(tester, find.byType(HandlesScreen));
    await pumpUntilFound(tester, find.text('@orbit'));
    await tapWhenReady(tester, find.text('Approve'));
    await pumpUntilFound(tester, find.text('No pending requests'));
    final decisions = api.requests.where(
      (request) =>
          request.method == 'POST' &&
          request.uri.path.endsWith('guardian/handle-proposals/1'),
    );
    expect(decisions.single.data, {'decision': 'approve'});
  });
}
