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
      '${parent ? 'guardian' : 'learner'} uses sourced pathways with transient own-learner evidence',
      (tester) async {
        final account = parent ? 21 : 11,
            planner = PathwaySmokeFixture(reader: account);
        final api = await startSmokeApp(tester, {
          ...(parent ? parentOfTwoRoutes() : learnerRoutes()),
          ...planner.routes,
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
                'pathway',
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
        await tapWhenReady(tester, find.text('Subject choice and pathways'));
        await tapWhenReady(tester, find.text('Synthetic programme'));
        await tapWhenReady(
          tester,
          find.byKey(const ValueKey('pathway-calculate')),
        );
        await pumpUntilFound(
          tester,
          find.text('Projected admission score: Unknown'),
        );
        await tester.enterText(
          find.byKey(const ValueKey('pathway-mark-4')),
          '62,50',
        );
        await tester.pump();
        expect(find.text('Projected admission score: Unknown'), findsNothing);
        await tapWhenReady(
          tester,
          find.byKey(const ValueKey('pathway-calculate')),
        );
        await pumpUntilFound(tester, find.text('Projected admission score: 4'));
        expect(
          (planner.calculations.last['marks'] as List).first['percentage_bp'],
          6250,
        );
        await tapWhenReady(tester, find.text('Released school marks'));
        await tapWhenReady(
          tester,
          find.byType(DropdownButtonFormField<int>).last,
        );
        await tapWhenReady(tester, find.text('Synthetic year').last);
        await tapWhenReady(
          tester,
          find.byKey(const ValueKey('pathway-calculate')),
        );
        await pumpUntilFound(
          tester,
          find.textContaining('Synthetic released grade'),
        );
        expect(planner.calculations.last['source'], 'released');
        expect(planner.calculations.last['marks'], isNull);
        await tapWhenReady(tester, find.text('All pathway sources'));
        await tapWhenReady(tester, find.text('Synthetic Grade 9 guide'));
        await tapWhenReady(
          tester,
          find.byKey(const ValueKey('pathway-choice-4')),
        );
        await tapWhenReady(
          tester,
          find.byKey(const ValueKey('pathway-choice-5')),
        );
        await tapWhenReady(
          tester,
          find.byKey(const ValueKey('pathway-calculate')),
        );
        await pumpUntilFound(
          tester,
          find.text('Meets the entered subject-choice rules'),
        );
        expect(planner.calculations.last['source'], 'selection');
        expect(find.textContaining('Projected admission score:'), findsNothing);
        await tapWhenReady(tester, find.text('All pathway sources'));
        await tapWhenReady(tester, find.text('Synthetic programme'));
        await pumpUntilFound(
          tester,
          find.byKey(const ValueKey('pathway-mark-4')),
        );
        await tester.enterText(
          find.byKey(const ValueKey('pathway-mark-4')),
          '88.50',
        );
        final before = planner.calculations.length;
        planner.allowed = false;
        await tapWhenReady(
          tester,
          find.byKey(const ValueKey('pathway-calculate')),
        );
        await pumpUntilFound(tester, find.text('Access withdrawn'));
        expect(find.byType(TextField), findsNothing);
        expect(planner.calculations.length, before);
        for (final request in api.requests.where(
          (r) => r.path.contains('/pathways/'),
        )) {
          expect(request.headers['X-School-Id'], '1');
          expect(request.headers['X-Account-Id'], '$account');
          expect(
            request.queryParameters,
            request.method == 'GET' && request.path.endsWith('/references')
                ? {'page': 1}
                : isEmpty,
          );
          if (request.method != 'GET') {
            expect(request.method, 'POST');
            expect(request.path, endsWith('/projection'));
          }
        }
        expect(tester.takeException(), isNull);
      },
    );
  }
  tearDownAll(() {
    final binding = IntegrationTestWidgetsFlutterBinding.instance;
    binding.reportData = {
      'test_results': binding.results.map(
        (name, result) =>
            MapEntry(name, result == 'success' ? 'passed' : 'failed'),
      ),
    };
  });
}
