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
  testWidgets(
    'Parent reviews sourced school text then explicitly contacts the office without copying records',
    (tester) async {
      final fixture = AssistanceSmokeFixture(reader: 21);
      final api = await startSmokeApp(tester, {
        ...parentOfTwoRoutes(),
        ...fixture.routes,
        'GET parent/get-notification': ok({
          'current_page': 1,
          'last_page': 1,
          'data': [
            {
              'id': 83,
              'type': 'school_assistance.parent_update',
              'title': 'School support update',
              'message': 'Open Ask the school to check your available school-support updates.',
              'date': '2026-10-26',
              'image': '',
              'school_id': 1,
              'reader_id': 21,
            },
          ],
        }),
        'GET v2/me/context': ok({
          ...academicContextFixture(role: 'Parent'),
          'user': {
            'id': 21,
            'first_name': 'Parent',
            'last_name': 'Mokoena',
            'name': 'Parent Mokoena',
            'email': parentEmail,
            'image': null,
            'roles': ['Parent'],
          },
          'feature_flags': {'module_switches': true},
          'modules': [
            for (final key in [
              'school',
              'people',
              'academics',
              'communication',
              'ask_school',
              'web_parent',
            ])
              {'key': key, 'read_only': false},
          ],
          'children': [
            for (final (id, name) in [(11, 'Thandi'), (12, 'Sipho')])
              {
                'id': id,
                'user_id': id,
                'first_name': name,
                'last_name': 'Mokoena',
                'name': '$name Mokoena',
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
      await tapWhenReady(tester, find.text('Login as Parent'));
      await pumpUntilFound(tester, find.text('Email or handle'));
      await enterField(tester, 0, parentEmail);
      await enterField(tester, 1, password);
      await tapWhenReady(
        tester,
        find.widgetWithText(CustomRoundedButton, 'Sign in'),
      );
      await pumpUntilFound(tester, find.byType(ParentHomeScreen));
      await assistanceTap(tester, 'Ask the school');
      await assistanceTap(tester, 'Read source');
      await assistanceFind(
        tester,
        find.textContaining('PRIVATE approved source opens at 08:00.'),
      );
      await assistanceType(
        tester,
        'Your school question',
        'PRIVATE explicit school question',
      );
      await assistanceTap(tester, 'Review selected sources and question');
      await assistanceFind(tester, find.text('Review before sharing'));
      await assistanceFind(
        tester,
        find.textContaining('PRIVATE explicit school question'),
      );
      expect(
        api.requested(
          'POST v2/school-assistance/turns/${AssistanceSmokeFixture.turn}/run',
        ),
        isFalse,
      );
      await assistanceTap(tester, 'Close private review');
      await assistanceTap(tester, 'Ask the school office');
      final handoff =
          fixture.writes.singleWhere((w) => w['action'] == 'open')['body']
              as Map;
      expect(handoff['text'], 'PRIVATE explicit school question');
      expect(handoff.containsKey('sources'), isFalse);
      expect(handoff.containsKey('records'), isFalse);
      await assistanceType(
        tester,
        'Your reply',
        'PRIVATE follow-up from Parent',
      );
      await assistanceTap(tester, 'Send reply');
      expect(
        fixture.writes.singleWhere(
          (w) => w['action'] == 'reply',
        )['body']['persona'],
        'parent',
      );
      await assistanceTap(tester, 'Review message translation');
      await assistanceFind(tester, find.text('Review before sharing'));
      expect(
        fixture.messages.first['text'],
        'PRIVATE explicit school question',
      );
      await assistanceTap(tester, 'Close private review');
      await tapWhenReady(tester, find.byTooltip('Back').first);
      await pumpUntilFound(tester, find.byType(ParentHomeScreen));
      await tapWhenReady(tester, find.byIcon(Icons.settings));
      await tapWhenReady(tester, find.text('Notifications'));
      await tapWhenReady(
        tester,
        find.byKey(const ValueKey('assistance-notice-83')),
      );
      await assistanceTap(tester, 'Open thread');
      await assistanceFind(
        tester,
        find.text('PRIVATE explicit school question'),
      );
      fixture.allowed = false;
      tester.binding.handleAppLifecycleStateChanged(AppLifecycleState.inactive);
      await tester.pump();
      tester.binding.handleAppLifecycleStateChanged(AppLifecycleState.resumed);
      await pumpUntilFound(
        tester,
        find.text(
          'This school information is no longer available to this account.',
        ),
      );
      expect(
        find.textContaining(
          'PRIVATE explicit school question',
          skipOffstage: false,
        ),
        findsNothing,
      );
      expect(
        find.textContaining('PRIVATE approved source', skipOffstage: false),
        findsNothing,
      );
      final reads = api.requests.where(
        (r) => r.uri.path.contains('/school-assistance/'),
      );
      expect(reads.isNotEmpty, isTrue);
      expect(
        reads.every(
          (r) =>
              r.headers['X-School-Id'] == '1' &&
              r.headers['X-Account-Id'] == '21',
        ),
        isTrue,
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
  await Scrollable.ensureVisible(tester.element(target.first), alignment: 0.5);
  await tester.pump(const Duration(milliseconds: 500));
}

Future<void> assistanceTap(WidgetTester tester, String text) async {
  final finder = find.text(text);
  await assistanceFind(tester, finder);
  expect(finder.first.hitTestable(), findsOneWidget);
  await tester.tap(finder.first.hitTestable());
  await tester.pump(const Duration(milliseconds: 500));
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
