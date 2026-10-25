import 'package:dio/dio.dart';
import 'package:eschool/ui/screens/parentHomeScreen.dart';
import 'package:eschool_academics/eschool_academics.dart';
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
    'parent reads own weekly update and opens its authorized home material',
    (tester) async {
      var denied = false;
      final api = await startSmokeApp(tester, {
        ...parentOfTwoRoutes(),
        'GET parent/get-notification': ok({
          'current_page': 1,
          'last_page': 1,
          'data': [
            {
              'id': 81,
              'title': 'Private weekly update available',
              'message': 'Open your private weekly learning update.',
              'type': 'digest.weekly',
              'date': '2026-02-16',
              'image': '',
              'is_read': 0,
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
          'feature_flags': {'module_switches': true, 'academic_content': true},
          'modules': [
            for (final key in [
              'school',
              'people',
              'academics',
              'academic_content',
              'digest',
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
        'GET v2/digests/access': (RequestOptions request) => denied
            ? throw DioException(
                requestOptions: request,
                type: DioExceptionType.badResponse,
                response: Response(
                  requestOptions: request,
                  statusCode: 403,
                  data: {
                    'error': true,
                    'message': 'Weekly access withdrawn',
                    'code': 403,
                  },
                ),
              )
            : ok({
                'school_id': 1,
                'account_id': 21,
                'parent': true,
                'can_manage': false,
                'children': [
                  {'id': 11, 'name': 'Thandi Mokoena'},
                  {'id': 12, 'name': 'Sipho Mokoena'},
                ],
                'language': 'en',
                'preference_revision': 0,
                'languages': ['en', 'zu'],
                'approved_languages': ['en'],
              }),
        'GET v2/digests/learners/11/weeks': ok({
          'items': [
            {
              'id': 8,
              'learner_id': 11,
              'language': 'en',
              'week_start': '2026-02-09',
              'week_end': '2026-02-15',
              'title': 'This week for Thandi',
              'opened': false,
            },
          ],
          'total': 1,
          'next_before': null,
        }),
        'GET v2/digests/8': ok({
          'id': 8,
          'learner_id': 11,
          'language': 'en',
          'week_start': '2026-02-09',
          'week_end': '2026-02-15',
          'template_version': 1,
          'title': 'This week for Thandi',
          'summary': 'Synthetic private summary: practice 1 of 2.',
          'expires_at': DateTime.now()
              .toUtc()
              .add(const Duration(hours: 1))
              .toIso8601String(),
          'evidence': {
            'marks': [],
            'attendance': {
              'recorded': 4,
              'present': 3,
              'absent': 1,
              'excused': 0,
            },
            'practice': {'total': 2, 'completed': 1},
            'events': [],
            'event_count': 0,
            'action_kind': 'practice',
            'content_version_id': 11,
          },
          'home_action': {
            'kind': 'practice',
            'learner_id': 11,
            'label': 'Open school materials together',
            'content_version_id': 11,
          },
        }),
        'GET v2/academics/content/learners/11': ok(contentLibraryFixture()),
        'GET v2/academics/content/learners/11/versions/11': ok({
          ...contentReaderFixture(learner: 11, account: 21),
          'server_time': DateTime.now().toUtc().toIso8601String(),
          'offline_until': DateTime.now()
              .toUtc()
              .add(const Duration(hours: 1))
              .toIso8601String(),
        }),
        'POST v2/academics/content/learners/11/versions/11/read': ok({
          'id': 11,
          'learner_id': 11,
          'read_at': DateTime.now().toUtc().toIso8601String(),
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
      await tapWhenReady(tester, find.text('Weekly learning update'));
      await pumpUntilFound(
        tester,
        find.text('Synthetic private summary: practice 1 of 2.'),
      );
      await tapWhenReady(tester, find.text('Open school materials together'));
      await pumpUntilFound(tester, find.text('One half equals two quarters.'));
      expect(
        api.requested('GET v2/academics/content/learners/11/versions/11'),
        true,
      );
      await tapWhenReady(tester, find.byTooltip('Back').first);
      await pumpUntilFound(tester, find.byType(LearningContentScreen));
      await tapWhenReady(tester, find.byTooltip('Back').first);
      await pumpUntilFound(tester, find.byType(WeeklyDigestScreen));
      await tapWhenReady(tester, find.byTooltip('Back').first);
      await pumpUntilFound(tester, find.byType(ParentHomeScreen));
      await tapWhenReady(tester, find.byIcon(Icons.settings));
      await tapWhenReady(tester, find.text('Notifications'));
      await pumpUntilFound(
        tester,
        find.text('Private weekly update available'),
      );
      await tapWhenReady(
        tester,
        find.byKey(const ValueKey('digest-notice-81')),
      );
      await pumpUntilFound(
        tester,
        find.text('Synthetic private summary: practice 1 of 2.'),
      );
      tester.binding.handleAppLifecycleStateChanged(AppLifecycleState.inactive);
      await tester.pump();
      expect(
        find.text('Synthetic private summary: practice 1 of 2.'),
        findsNothing,
      );
      denied = true;
      tester.binding.handleAppLifecycleStateChanged(AppLifecycleState.resumed);
      await pumpUntilFound(tester, find.text('Weekly access withdrawn'));
      expect(
        find.text('Synthetic private summary: practice 1 of 2.'),
        findsNothing,
      );
      final reads = api.requests.where(
        (request) => request.path.contains('/digests/'),
      );
      expect(reads, isNotEmpty);
      expect(
        reads.every(
          (request) =>
              request.headers['X-School-Id'] == '1' &&
              request.headers['X-Account-Id'] == '21' &&
              !request.path.contains('/learners/12/'),
        ),
        true,
      );
      expect(
        api.requests.any(
          (request) => request.path.contains('/learning/practice/'),
        ),
        false,
      );
      expect(tester.takeException(), isNull);
      await tester.pumpWidget(const SizedBox.shrink());
    },
  );
}
