import 'package:dio/dio.dart';
import 'package:eschool/ui/screens/home/homeScreen.dart';
import 'package:eschool/ui/screens/parentHomeScreen.dart';
import 'package:eschool_academics/testing.dart';
import 'package:eschool_core/eschool_core.dart';
import 'package:eschool_core/testing.dart';
import 'package:eschool_fees/testing.dart';
import 'package:flutter_test/flutter_test.dart';
import 'package:flutter/widgets.dart';
import 'package:integration_test/integration_test.dart';

import 'support/fixtures.dart';
import 'support/smoke.dart';

void main() {
  IntegrationTestWidgetsFlutterBinding.ensureInitialized();
  for (final parent in [false, true]) {
    testWidgets(
      '${parent ? 'parent' : 'learner'} sees only authorised fee statements',
      (tester) async {
        final actor = parent ? 21 : 11;
        var allowed = true;
        Object privateRead(Object data) => allowed
            ? ok(data)
            : FakeResponse({
                'error': true,
                'message': 'Forbidden',
              }, status: 403);
        final api = await startSmokeApp(tester, {
          ...(parent ? parentOfTwoRoutes() : learnerRoutes()),
          'GET v2/me/context': ok({
            ...academicContextFixture(role: parent ? 'Parent' : 'Student'),
            'user': {
              'id': actor,
              'first_name': 'Synthetic',
              'last_name': 'Parent',
              'name': 'Synthetic Parent',
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
                'fees',
                'web_parent',
                'web_learner',
              ])
                {'key': key, 'read_only': false},
            ],
            if (parent)
              'children': [
                for (final child in [11, 12])
                  {
                    'id': child,
                    'user_id': child,
                    'first_name': child == 11 ? 'Asha' : 'Cara',
                    'last_name': 'Synthetic',
                    'name': child == 11 ? 'Asha Synthetic' : 'Cara Synthetic',
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
          'GET v2/parent/fees/accounts': (RequestOptions _) =>
              privateRead(feeCreditedAccountsFixture()),
          'GET v2/parent/fees/accounts/7/statement': (RequestOptions _) =>
              privateRead(feeVatStatementFixture()),
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
        if (!parent) {
          expect(find.text('Fee statements'), findsNothing);
          expect(
            api.requests.where((r) => r.uri.path.contains('/parent/fees/')),
            isEmpty,
          );
          return;
        }
        await tapWhenReady(tester, find.text('Fee statements'));
        await tapWhenReady(tester, find.text('Open statement'));
        await pumpUntilFound(tester, find.text('Your learner charges'));
        expect(find.text('Your invoice balance still due'), findsOneWidget);
        expect(find.text('Your held credit'), findsOneWidget);
        await tester.scrollUntilVisible(
          find.text('Asha Synthetic'),
          200,
          scrollable: find.byType(Scrollable).last,
        );
        expect(find.text('Asha Synthetic'), findsOneWidget);
        expect(find.text('VAT included in your charge'), findsWidgets);
        expect(find.text('Your charge before VAT'), findsWidgets);
        expect(find.text('Additional fee'), findsWidgets);
        expect(
          find.text('Family charge apportioned to this learner'),
          findsWidgets,
        );
        await tester.scrollUntilVisible(
          find.text('Cara Synthetic'),
          200,
          scrollable: find.byType(Scrollable).last,
        );
        expect(find.text('Cara Synthetic'), findsOneWidget);
        await tester.scrollUntilVisible(
          find.text('Your receipts and credits'),
          300,
          scrollable: find.byType(Scrollable).last,
        );
        await tester.pump();
        await tester.scrollUntilVisible(
          find.text('FEE-PAY-SYNTHETIC-1'),
          200,
          scrollable: find.byType(Scrollable).last,
        );
        expect(find.text('FEE-PAY-SYNTHETIC-1'), findsOneWidget);
        await tester.scrollUntilVisible(
          find.text('Reserved for learner: Cara Synthetic'),
          200,
          scrollable: find.byType(Scrollable).last,
        );
        await tester.pump();
        expect(find.text('FEE-PAY-SYNTHETIC-2'), findsOneWidget);
        await tester.scrollUntilVisible(
          find.text('FEE-CN-SYNTHETIC-1'),
          200,
          scrollable: find.byType(Scrollable).last,
        );
        expect(find.text('Billing correction'), findsOneWidget);
        expect(find.text('Restored to receipts'), findsOneWidget);
        expect(find.text('VAT included in this credit'), findsOneWidget);
        expect(find.text('Credit before VAT'), findsOneWidget);
        allowed = false;
        await tapWhenReady(tester, find.byTooltip('Refresh fee statements'));
        await pumpUntilFound(tester, find.text('Your fee access changed'));
        expect(find.text('Synthetic family'), findsNothing);
        expect(find.text('FEE-PAY-SYNTHETIC-1'), findsNothing);
        expect(find.text('FEE-CN-SYNTHETIC-1'), findsNothing);
        expect(find.text('Asha Synthetic'), findsNothing);
        final reads = api.requests.where(
          (r) => r.uri.path.contains('/parent/fees/'),
        );
        expect(reads, isNotEmpty);
        expect(
          reads.every(
            (r) =>
                r.headers['X-School-Id'] == '1' &&
                r.headers['X-Account-Id'] == '$actor',
          ),
          isTrue,
        );
        expect(tester.takeException(), isNull);
      },
    );
  }
}
