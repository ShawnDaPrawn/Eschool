import 'package:eschool_academics/testing.dart';
import 'package:eschool_core/eschool_core.dart';
import 'package:flutter/material.dart';
import 'package:flutter_test/flutter_test.dart';
import 'package:integration_test/integration_test.dart';

import 'support/fixtures.dart';
import 'support/smoke.dart';

void main() {
  IntegrationTestWidgetsFlutterBinding.ensureInitialized();
  testWidgets(
    'appointed principal explicitly claims school support and reassigns without retaining message bodies',
    (tester) async {
      final fixture = AssistanceSmokeFixture(reader: 12, office: true);
      final api = await startSmokeApp(tester, {
        ...teacherRoutes(),
        ...fixture.routes,
        'GET v2/me/context': ok({
          ...academicContextFixture(),
          'user': {
            'id': 12,
            'first_name': 'Synthetic',
            'last_name': 'Principal',
            'name': 'Synthetic Principal',
            'email': teacherEmail,
            'image': null,
            'roles': ['Teacher'],
          },
          'feature_flags': {'module_switches': true},
          'modules': [
            for (final key in [
              'school',
              'people',
              'academics',
              'communication',
              'ask_school',
              'web_teacher',
            ])
              {'key': key, 'read_only': false},
          ],
        }),
      });
      await pumpUntilFound(tester, find.text('Email or handle'));
      await enterField(tester, 0, teacherEmail);
      await enterField(tester, 1, password);
      await tapWhenReady(
        tester,
        find.widgetWithText(CustomRoundedButton, 'Sign in'),
      );
      await assistanceOfficeJourney(tester, fixture);
      expect(
        api.requests
            .where((r) => r.uri.path.contains('/school-assistance/'))
            .every(
              (r) =>
                  r.headers['X-School-Id'] == '1' &&
                  r.headers['X-Account-Id'] == '12',
            ),
        isTrue,
      );
      expect(
        api.requestedPaths.where((p) => p.contains('/family/learners/')),
        isEmpty,
      );
      expect(tester.takeException(), isNull);
    },
  );
}

Future<void> assistanceFind(WidgetTester tester, Finder target) async {
  FocusManager.instance.primaryFocus?.unfocus();
  await tester.pump(const Duration(milliseconds: 300));
  final scroll = find.byType(Scrollable);
  if (target.evaluate().isEmpty && scroll.evaluate().isNotEmpty) {
    await tester.drag(scroll.first, const Offset(0, 9000));
    await tester.pumpAndSettle();
    await tester.scrollUntilVisible(
      target,
      250,
      scrollable: scroll.first,
      maxScrolls: 60,
    );
  }
  await pumpUntilFound(tester, target);
  await tester.ensureVisible(target.first);
  await tester.pumpAndSettle();
}

Future<void> assistanceTap(WidgetTester tester, String text) async {
  final finder = find.text(text);
  await assistanceFind(tester, finder);
  await tapWhenReady(tester, finder.first);
  await tester.pumpAndSettle();
}

Future<void> assistanceType(
  WidgetTester tester,
  String label,
  String text,
) async {
  final finder = find.byWidgetPredicate(
    (w) => w is TextField && w.decoration?.labelText == label,
  );
  await assistanceFind(tester, finder);
  await tester.enterText(finder, text);
  await tester.pump();
}

Future<void> assistanceOfficeJourney(
  WidgetTester tester,
  AssistanceSmokeFixture fixture, {
  bool openEntry = true,
}) async {
  if (openEntry) await assistanceTap(tester, 'School support office');
  await assistanceTap(tester, 'Claim request');
  await assistanceFind(tester, find.text('PRIVATE original Parent question'));
  await assistanceType(tester, 'Your reply', 'PRIVATE original office reply');
  await assistanceTap(tester, 'Send reply');
  expect(
    fixture.writes
        .where((w) => w['action'] == 'reply')
        .single['body']['persona'],
    'office',
  );
  await assistanceTap(tester, 'Review message translation');
  await assistanceFind(tester, find.text('Review before sharing'));
  expect(
    find.text('PRIVATE original Parent question', skipOffstage: false),
    findsWidgets,
  );
  expect(fixture.messages.first['text'], 'PRIVATE original Parent question');
  await assistanceTap(tester, 'Close private review');
  final choice = find.byType(DropdownButtonFormField<int>);
  await assistanceFind(tester, choice);
  await tester.tap(choice);
  await tester.pumpAndSettle();
  await tester.tap(find.text('Current second office reader').last);
  await tester.pumpAndSettle();
  await assistanceType(
    tester,
    'Reason for office handoff',
    'Private explicit office reassignment',
  );
  await assistanceTap(tester, 'Confirm office handoff');
  expect(
    fixture.writes
        .where((w) => w['action'] == 'reassign')
        .single['body']['assigned_user_id'],
    13,
  );
  expect(
    find.text('PRIVATE original Parent question', skipOffstage: false),
    findsNothing,
  );
  expect(
    find.text('PRIVATE original office reply', skipOffstage: false),
    findsNothing,
  );
}
