import 'package:eschool/ui/screens/home/homeScreen.dart';
import 'package:eschool/ui/screens/parentHomeScreen.dart';
import 'package:eschool_academics/testing.dart';
import 'package:eschool_core/eschool_core.dart';
import 'package:flutter/material.dart';
import 'package:flutter_test/flutter_test.dart';
import 'package:integration_test/integration_test.dart';

import 'support/fixtures.dart';
import 'support/smoke.dart';

void main() {
  IntegrationTestWidgetsFlutterBinding.ensureInitialized();
  for (final parent in [false, true]) {
    testWidgets(
      '${parent ? 'guardian' : 'learner'} opens own authorized digital card and freezes it',
      (tester) async {
        final account = parent ? 21 : 11,
            cards = CardSmokeFixture(personId: 11, type: 'learner');
        final api = await startSmokeApp(tester, {
          ...(parent ? parentOfTwoRoutes() : learnerRoutes()),
          ...cards.routes(),
          'GET v2/me/context': ok({
            ...academicContextFixture(role: parent ? 'Parent' : 'Student'),
            'user': {
              'id': account,
              'first_name': 'Synthetic',
              'last_name': 'Mokoena',
              'name': 'Synthetic Mokoena',
              'email': parent ? parentEmail : 'learner@example.test',
              'image': null,
              'roles': [parent ? 'Parent' : 'Student'],
            },
            'feature_flags': {'module_switches': true},
            'modules': [
              for (final key in [
                'school',
                'people',
                'academics',
                'cards',
                'web_parent',
                'web_learner',
              ])
                {'key': key, 'read_only': false},
            ],
            if (parent)
              'children': [
                {
                  'id': 11,
                  'user_id': 11,
                  'first_name': 'Synthetic',
                  'last_name': 'Learner',
                  'name': 'Synthetic Learner',
                  'approved': true,
                  'image': null,
                  'school': {
                    'id': 1,
                    'name': 'Smoke Test School',
                    'short_name': null,
                    'crest_url': null,
                  },
                },
              ],
          }),
        });
        await tapWhenReady(
          tester,
          find.text(parent ? 'Login as Parent' : 'Login as Student'),
        );
        await pumpUntilFound(
          tester,
          find.text(
            parent ? 'Email or handle' : 'Student number, GR number or handle',
          ),
        );
        await enterField(tester, 0, parent ? parentEmail : grNumber);
        await enterField(tester, 1, password);
        await tapWhenReady(
          tester,
          find.widgetWithText(CustomRoundedButton, 'Sign in'),
        );
        await pumpUntilFound(
          tester,
          find.byType(parent ? ParentHomeScreen : HomeScreen),
        );
        await tapWhenReady(tester, find.text('School identity cards'));
        await tapWhenReady(tester, find.text('Show secure code'));
        await pumpUntilFound(
          tester,
          find.byKey(
            const ValueKey(
              'secure-card-code-8aef50e7-2429-4d2c-a523-668ae436c4d4',
            ),
          ),
        );
        await tapWhenReady(tester, find.text('Freeze card'));
        await pumpUntilFound(tester, find.byType(TextField));
        await tester.enterText(find.byType(TextField), 'Synthetic lost card');
        await tester.pump();
        await tapWhenReady(
          tester,
          find.widgetWithText(FilledButton, 'Freeze card'),
        );
        await pumpUntilFound(
          tester,
          find.text('Contact your school to restore or replace this card.'),
        );
        expect(cards.freezes, 1);
        cards.allowed = false;
        tester.binding.handleAppLifecycleStateChanged(
          AppLifecycleState.inactive,
        );
        await tester.pump();
        tester.binding.handleAppLifecycleStateChanged(
          AppLifecycleState.resumed,
        );
        await pumpUntilFound(
          tester,
          find.text('Your card access changed. Reopen this screen.'),
        );
        final reads = api.requests.where((r) => r.uri.path.contains('/cards/'));
        expect(reads, isNotEmpty);
        expect(
          reads.every(
            (r) =>
                r.headers['X-School-Id'] == '1' &&
                r.headers['X-Account-Id'] == '$account',
          ),
          isTrue,
        );
        expect(tester.takeException(), isNull);
      },
    );
  }
}
