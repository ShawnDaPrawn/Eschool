import 'package:dio/dio.dart';
import 'package:eschool/app/deepLinks.dart';
import 'package:eschool/app/routes.dart';
import 'package:eschool_session/eschool_session.dart';
import 'package:eschool/ui/screens/parentHomeScreen.dart';
import 'package:eschool_academics/eschool_academics.dart';
import 'package:eschool_academics/testing.dart';
import 'package:eschool_core/eschool_core.dart';
import 'package:flutter_test/flutter_test.dart';
import 'package:flutter/widgets.dart';
import 'package:integration_test/integration_test.dart';

import 'support/fixtures.dart';
import 'support/smoke.dart';

void main() {
  IntegrationTestWidgetsFlutterBinding.ensureInitialized();
  testWidgets('parent opens only selected child published learning material', (
    tester,
  ) async {
    var disconnected = false;
    Object connected(RequestOptions request, Object data) {
      if (disconnected)
        throw DioException(
          requestOptions: request,
          type: DioExceptionType.connectionError,
        );
      return ok(data);
    }

    Map<String, dynamic> liveReader({int learner = 11}) {
      final clock = DateTime.now().toUtc();
      return {
        ...contentReaderFixture(learner: learner, account: 21),
        'server_time': clock.toIso8601String(),
        'offline_until': clock.add(const Duration(hours: 1)).toIso8601String(),
      };
    }

    final api = await startSmokeApp(tester, {
      ...parentOfTwoRoutes(),
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
        'feature_flags': {'academic_content': true},
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
      'GET v2/academics/content/learners/11': (RequestOptions request) =>
          connected(request, contentLibraryFixture()),
      'GET v2/academics/content/learners/12': ok(
        contentLibraryFixture(learner: 12),
      ),
      'GET v2/academics/content/learners/12/versions/11': ok(
        liveReader(learner: 12),
      ),
      'POST v2/academics/content/learners/12/versions/11/read': ok({
        'id': 11,
        'learner_id': 12,
        'read_at': '2026-10-24T10:00:00Z',
      }),
      'GET v2/academics/content/learners/11/versions/11': (
        RequestOptions request,
      ) => connected(request, liveReader()),
      'POST v2/academics/content/learners/11/saved-access':
          (RequestOptions request) => connected(request, {
            'school_id': 1,
            'reader_id': 21,
            'learner_id': 11,
            'allowed_version_ids': [11],
            'server_time': DateTime.now().toUtc().toIso8601String(),
            'offline_until': DateTime.now()
                .toUtc()
                .add(const Duration(hours: 1))
                .toIso8601String(),
          }),
      'POST v2/academics/content/learners/11/versions/11/read': ok({
        'id': 11,
        'learner_id': 11,
        'read_at': '2026-10-24T10:00:00Z',
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
    await tapWhenReady(tester, find.text('Learning content'));
    await pumpUntilFound(tester, find.byType(LearningContentScreen));
    await tapWhenReady(tester, find.text('Fractions'));
    await pumpUntilFound(tester, find.text('One half equals two quarters.'));
    await tapWhenReady(tester, find.text('Save for offline reading'));
    await pumpUntilFound(
      tester,
      find.text(
        'Saved privately for offline reading. Copies expire within one day.',
      ),
    );
    disconnected = true;
    tester.binding.handleAppLifecycleStateChanged(AppLifecycleState.inactive);
    await tester.pump(const Duration(milliseconds: 100));
    tester.binding.handleAppLifecycleStateChanged(AppLifecycleState.resumed);
    await pumpUntilFound(
      tester,
      find.text(
        'Saved offline copy. Connect to check current access. This copy expires within one day.',
      ),
    );
    expect(find.text('One half equals two quarters.'), findsOneWidget);
    disconnected = false;
    final requests = api.requests
        .where((r) => r.uri.path.contains('/academics/content/'))
        .toList();
    expect(requests.length, greaterThanOrEqualTo(3));
    expect(
      requests.every(
        (r) =>
            r.headers['X-School-Id'] == '1' &&
            !r.uri.path.contains('/learners/12'),
      ),
      isTrue,
    );
    expect(api.requested('GET v2/academics/content/configuration'), isFalse);
    openDeepLink(
      const DeepLinkTarget(
        Routes.academicContent,
        arguments: ContentLinkTarget(learner: 999, version: 11),
      ),
      signedIn: true,
    );
    await pumpUntilFound(tester, find.text('Not available for this account'));
    expect(
      api.requests.any((r) => r.uri.path.contains('/learners/999')),
      false,
    );
    openDeepLink(
      const DeepLinkTarget(
        Routes.academicContent,
        arguments: ContentLinkTarget(learner: 12, version: 11),
      ),
      signedIn: true,
    );
    await pumpUntilFound(tester, find.text('One half equals two quarters.'));
    expect(
      api.requested('GET v2/academics/content/learners/12/versions/11'),
      true,
    );
    expect(tester.takeException(), isNull);
  });
}
