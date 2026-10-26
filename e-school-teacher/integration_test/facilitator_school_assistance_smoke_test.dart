import 'package:dio/dio.dart';
import 'package:eschool_academics/eschool_academics.dart';
import 'package:eschool_academics/testing.dart';
import 'package:eschool_session/eschool_session.dart';
import 'package:eschool_teacher/app/app.dart';
import 'package:eschool_teacher/facilitator/facilitator_repository.dart';
import 'package:flutter/material.dart';
import 'package:flutter_test/flutter_test.dart';
import 'package:hive/hive.dart';
import 'package:integration_test/integration_test.dart';

import 'support/wellbeing_fixture.dart';
import 'support/smoke.dart' show pumpUntilFound, tapWhenReady;

class _Office extends ClinicalStaffFixture {
  bool permitted = true;
  @override
  Map<String, dynamic> profile() => {
    ...super.profile(),
    'name': 'Synthetic appointed principal',
    'roles': ['Facilitator'],
    'features': {
      'groups': false,
      'messages': false,
      'counselling': false,
      'school_assistance': permitted,
      'private_notices': permitted,
    },
  };
}

void main() {
  IntegrationTestWidgetsFlutterBinding.ensureInitialized();
  testWidgets(
    'dedicated office profile uses explicit source grants without Teacher storage',
    (tester) async {
      final staff = _Office(),
          fixture = AssistanceSmokeFixture(reader: 12, office: true);
      staff.api.routes.addAll(fixture.routes);
      bool marked = false;
      staff.api.routes.addAll({
        'GET v2/staff/private-notices': (RequestOptions _) => {
          'error': false,
          'data': {
            'school_id': 1,
            'reader_id': 12,
            'writable': true,
            'page': 1,
            'last_page': 1,
            'unread_count': marked ? 0 : 1,
            'items': [
              {
                'id': 70,
                'feature': 'school_assistance',
                'title': 'School support update',
                'message': 'Open the school support office to review available requests.',
                'writable': true,
                'read_at': marked ? '2026-10-26T10:00:00Z' : null,
              },
            ],
          },
        },
        'POST v2/staff/private-notices/70/read': (RequestOptions _) {
          marked = true;
          return {
            'error': false,
            'data': {
              'school_id': 1,
              'reader_id': 12,
              'id': 70,
              'read_at': '2026-10-26T10:00:00Z',
            },
          };
        },
      });
      final repository = FacilitatorRepository(
        mode: AuthMode.bearer,
        createDio: () => Dio()..httpClientAdapter = staff.api,
      );
      await initializeApp(facilitatorRepository: repository);
      await pumpUntilFound(tester, find.text('Facilitator sign-in'));
      Finder field(String label) => find.byWidgetPredicate(
        (w) => w is TextField && w.decoration?.labelText == label,
      );
      await tester.enterText(field('School code'), 'jacaranda');
      await tester.enterText(field('Email or handle'), 'office@example.test');
      await tester.enterText(field('Password'), 'SyntheticStaff2026');
      await tapWhenReady(tester, find.text('Sign in'));
      await assistanceTap(tester, 'Private staff updates');
      await assistanceTap(tester, 'School support update');
      await assistanceOfficeJourney(tester, fixture, openEntry: false);
      expect(marked, isTrue);
      staff.permitted = false;
      fixture.allowed = false;
      await tester.runAsync(repository.refresh);
      await tester.pumpAndSettle();
      expect(find.byType(SchoolAssistanceScreen), findsNothing);
      expect(find.textContaining('PRIVATE', skipOffstage: false), findsNothing);
      expect(Hive.isBoxOpen('authBox'), isFalse);
      expect(
        staff.api.requests
            .where((r) => r.uri.path.contains('/school-assistance/'))
            .every(
              (r) =>
                  r.headers['X-School-Id'] == '1' &&
                  r.headers['X-Account-Id'] == '12',
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
