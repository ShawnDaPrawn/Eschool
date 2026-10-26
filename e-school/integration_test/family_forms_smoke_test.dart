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
      '${parent ? 'guardian' : 'learner'} has only authorized consent and health access',
      (tester) async {
        final account = parent ? 21 : 11,
            forms = FamilyFormSmokeFixture(accountId: account);
        final api = await startSmokeApp(tester, {
          ...(parent ? parentOfTwoRoutes() : learnerRoutes()),
          ...forms.routes,
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
                'family_forms',
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
        if (!parent) {
          expect(find.text('Family consent and health'), findsNothing);
          expect(
            api.requests.where((r) => r.uri.path.contains('/family-forms/')),
            isEmpty,
          );
          return;
        }
        await tapWhenReady(tester, find.text('Family consent and health'));
        await tapWhenReady(tester, find.text('Synthetic trip'));
        await pumpUntilFound(tester, find.text(privateWording));
        await tester.tap(find.byType(DropdownButtonFormField<String>));
        await tester.pumpAndSettle();
        await tapWhenReady(tester, find.text('Yes').last);
        await tester.enterText(find.byType(TextField), 'Synthetic Guardian');
        await tester.ensureVisible(find.byType(CheckboxListTile));
        await tester.tap(find.byType(CheckboxListTile));
        await tapWhenReady(tester, find.text('Record my signed decision'));
        await tapWhenReady(tester, find.text('Retry the same change'));
        await pumpUntilFound(tester, find.text('Last recorded decision: Yes'));
        expect(forms.effects, 1);
        expect(forms.receipts, hasLength(1));
        await tapWhenReady(tester, find.text('Back to consent requests'));
        await tapWhenReady(tester, find.text('Health forms'));
        await pumpUntilFound(tester, find.text('Synthetic private allergy'));
        await tester.enterText(
          find.byType(TextFormField),
          'Synthetic updated allergy',
        );
        await tapWhenReady(
          tester,
          find.widgetWithText(FilledButton, 'Save profile'),
        );
        await pumpUntilFound(tester, find.text('Profile saved'));
        expect(forms.saves, 1);
        forms.allowed = false;
        tester.binding.handleAppLifecycleStateChanged(
          AppLifecycleState.inactive,
        );
        await tester.pump();
        tester.binding.handleAppLifecycleStateChanged(
          AppLifecycleState.resumed,
        );
        await pumpUntilFound(
          tester,
          find.text('Your family access changed. Reopen this screen.'),
        );
        expect(find.text('Synthetic updated allergy'), findsNothing);
        expect(find.text(privateWording), findsNothing);
        final requests = api.requests.where(
          (r) => r.uri.path.contains('/family-forms/'),
        );
        expect(requests, isNotEmpty);
        expect(
          requests.every(
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
