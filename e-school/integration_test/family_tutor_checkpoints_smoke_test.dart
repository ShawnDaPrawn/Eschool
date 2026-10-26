import 'dart:convert';

import 'package:dio/dio.dart';
import 'package:eschool/ui/screens/home/homeScreen.dart';
import 'package:eschool_academics/testing.dart';
import 'package:eschool_core/eschool_core.dart';
import 'package:eschool_core/testing.dart';
import 'package:flutter/material.dart';
import 'package:flutter_test/flutter_test.dart';
import 'package:integration_test/integration_test.dart';

import 'support/fixtures.dart';
import 'support/smoke.dart';

const sessionId = '10000000-0000-4000-8000-000000000001';
const checkpointId = '10000000-0000-4000-8000-000000000006';
Future<void> pressCheck(WidgetTester tester, String label) async {
  final f = find.text(label).last;
  if (f.evaluate().isEmpty)
    await tester.scrollUntilVisible(
      f,
      220,
      scrollable: find.byType(Scrollable).first,
    );
  await tester.ensureVisible(f);
  await tapWhenReady(tester, f);
  await tester.pump(const Duration(milliseconds: 300));
}

void main() {
  IntegrationTestWidgetsFlutterBinding.ensureInitialized();
  testWidgets(
    'Student selects published topic and recovers one verified first answer without external inference',
    (tester) async {
      bool created = false, answered = false, permitted = true;
      String? issue;
      final writes = <Map<String, dynamic>>[];
      Map<String, dynamic> scope(Map<String, dynamic> body) => {
        'school_id': 1,
        'reader_id': 11,
        ...body,
      };
      Object guard(Map<String, dynamic> body) => permitted
          ? ok(scope(body))
          : const FakeResponse({'message': 'Access withdrawn'}, status: 403);
      Map<String, dynamic> check() => {
        'id': checkpointId,
        'session_id': sessionId,
        'learner_id': 11,
        'topic_id': 6,
        'operation_id': issue,
        'answer_operation_id': answered ? writes.last['operation_id'] : null,
        'state': answered ? 'answered' : 'ready',
        'question': {
          'kind': 'number',
          'prompt': 'PRIVATE-CHECK: one half as a decimal?',
          'options': [],
        },
        'correct': answered ? true : null,
        'explanation': answered
            ? 'APPROVED-CHECK: divide into two equal parts.'
            : null,
        'answered_at': answered ? '2026-10-26T10:15:30Z' : null,
        'verified_by': answered ? 'approved_answer_definition' : null,
      };
      Map<String, dynamic> body(RequestOptions r) => Map<String, dynamic>.from(
        r.data is String ? jsonDecode(r.data as String) : r.data as Map,
      );
      final api = await startSmokeApp(tester, {
        ...learnerRoutes(),
        'GET v2/me/context': ok({
          ...academicContextFixture(role: 'Student'),
          'user': {
            'id': 11,
            'first_name': 'Synthetic',
            'last_name': 'Learner',
            'name': 'Synthetic Learner',
            'email': 'learner@example.test',
            'image': null,
            'roles': ['Student'],
          },
          'roles': ['Student'],
          'approval': {'status': 'approved', 'approved_by_count': 1},
          'modules': [
            for (final key in [
              'school',
              'people',
              'academics',
              'tutor',
              'daily_practice',
              'web_learner',
            ])
              {'key': key, 'read_only': false},
          ],
        }),
        'GET v2/tutor/configuration': (RequestOptions _) => guard({
          'learner_id': 11,
          'session_year_id': 2,
          'groups': [
            {
              'id': 3,
              'name': 'Synthetic maths',
              'subject_id': 4,
              'subject_name': 'Mathematics',
              'sources': [
                {'id': 5, 'title': 'Published working'},
              ],
              'topics': [
                {'id': 6, 'name': 'Fractions'},
              ],
            },
          ],
          'inference_enabled': false,
          'photo_requires_approved_crop': true,
          'feedback_is_provisional': true,
          'checkpoints_enabled': true,
        }),
        'GET v2/tutor/sessions': (RequestOptions _) => guard({
          'learner_id': 11,
          'page': 1,
          'last_page': 1,
          'sessions': created
              ? [
                  {'id': sessionId, 'subject_id': 4, 'state': 'open'},
                ]
              : [],
        }),
        'POST v2/tutor/sessions': (RequestOptions r) {
          expect(body(r)['topic_id'], 6);
          created = true;
          return guard({
            'id': sessionId,
            'learner_id': 11,
            'group_id': 3,
            'subject_id': 4,
            'session_year_id': 2,
            'state': 'open',
            'topic_id': 6,
          });
        },
        'GET v2/tutor/sessions/$sessionId': (RequestOptions _) => guard({
          'id': sessionId,
          'learner_id': 11,
          'subject_id': 4,
          'group_id': 3,
          'session_year_id': 2,
          'state': 'open',
          'topic_id': 6,
          'page': 1,
          'last_page': 1,
          'turns': [],
          'pending_photos': [],
          'checkpoints': issue == null
              ? []
              : [
                  {
                    'id': checkpointId,
                    'operation_id': issue,
                    'state': answered ? 'answered' : 'ready',
                  },
                ],
        }),
        'POST v2/tutor/sessions/$sessionId/checkpoints': (RequestOptions r) {
          issue = body(r)['operation_id'] as String;
          return guard(check());
        },
        'GET v2/tutor/sessions/$sessionId/checkpoints/$checkpointId': (
          RequestOptions _,
        ) => guard(check()),
        'POST v2/tutor/sessions/$sessionId/checkpoints/$checkpointId/answer':
            (RequestOptions r) {
              writes.add(body(r));
              if (writes.length == 1)
                return const FakeResponse({
                  'message': 'Synthetic response lost',
                }, status: 503);
              expect(writes.last, writes.first);
              answered = true;
              return guard(check());
            },
      });
      await tapWhenReady(tester, find.text('Login as Student'));
      await pumpUntilFound(
        tester,
        find.text('Student number, GR number or handle'),
      );
      await enterField(tester, 0, grNumber);
      await enterField(tester, 1, password);
      await tapWhenReady(
        tester,
        find.widgetWithText(CustomRoundedButton, 'Sign in'),
      );
      await pumpUntilFound(tester, find.byType(HomeScreen));
      await tapWhenReady(tester, find.text('Ask your tutor'));
      await pumpUntilFound(tester, find.byKey(const ValueKey('tutor-topic-3')));
      await tapWhenReady(tester, find.byKey(const ValueKey('tutor-topic-3')));
      await tapWhenReady(tester, find.text('Fractions').last);
      await pressCheck(tester, 'Start a learning conversation');
      await pumpUntilFound(tester, find.text('Verified practice check'));
      await pressCheck(tester, 'Start an approved check');
      await pumpUntilFound(tester, find.textContaining('PRIVATE-CHECK'));
      expect(find.textContaining('APPROVED-CHECK'), findsNothing);
      await tester.ensureVisible(find.byType(TextField).first);
      await tester.enterText(find.byType(TextField).first, '0.500');
      await pressCheck(tester, 'Check my first answer');
      await pumpUntilFound(tester, find.text('Retry exact first answer'));
      tester.binding.handleAppLifecycleStateChanged(AppLifecycleState.inactive);
      await tester.pump();
      expect(find.textContaining('PRIVATE-CHECK'), findsNothing);
      tester.binding.handleAppLifecycleStateChanged(AppLifecycleState.resumed);
      await pumpUntilFound(tester, find.text('Retry exact first answer'));
      await pressCheck(tester, 'Retry exact first answer');
      await pumpUntilFound(tester, find.text('Verified correct'));
      expect(find.textContaining('APPROVED-CHECK'), findsOneWidget);
      expect(writes.length, 2);
      expect(writes[0], writes[1]);
      expect(
        api.requestedPaths.where(
          (p) => p.contains('/messages') || p.contains('/marks'),
        ),
        isEmpty,
      );
      expect(
        api.requests
            .where((r) => r.uri.path.contains('/tutor/'))
            .every(
              (r) =>
                  r.headers['X-School-Id'] == '1' &&
                  r.headers['X-Account-Id'] == '11',
            ),
        true,
      );
      permitted = false;
      tester.binding.handleAppLifecycleStateChanged(AppLifecycleState.inactive);
      await tester.pump();
      tester.binding.handleAppLifecycleStateChanged(AppLifecycleState.resumed);
      await pumpUntilFound(
        tester,
        find.text('Your tutor access changed. Reopen it after refreshing.'),
      );
      expect(find.textContaining('PRIVATE-CHECK'), findsNothing);
      expect(find.textContaining('APPROVED-CHECK'), findsNothing);
    },
  );
}
