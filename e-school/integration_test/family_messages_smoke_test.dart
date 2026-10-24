import 'package:eschool/app/deepLinks.dart';
import 'package:eschool/app/routes.dart';
import 'package:eschool/ui/screens/parentHomeScreen.dart';
import 'package:eschool_academics/eschool_academics.dart';
import 'package:eschool_academics/testing.dart';
import 'package:eschool_core/eschool_core.dart';
import 'package:eschool_core/testing.dart';
import 'package:eschool_session/eschool_session.dart';
import 'package:flutter/material.dart';
import 'package:flutter_test/flutter_test.dart';
import 'package:integration_test/integration_test.dart';

import 'support/fixtures.dart';
import 'support/smoke.dart';

void main() {
  IntegrationTestWidgetsFlutterBinding.ensureInitialized();
  testWidgets(
    'parent reads only authorized school messages and clears withdrawal',
    (tester) async {
      var withdrawn = false, acknowledged = false;
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
          'feature_flags': {
            'module_switches': true,
            'communication_messages': true,
          },
          'modules': [
            for (final key in [
              'school',
              'people',
              'academics',
              'communication',
              'web_parent',
              'web_learner',
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
        'GET v2/communication/configuration': ok(
          messageConfigurationFixture(staff: false, account: 21),
        ),
        'GET v2/communication/messages': (_) => ok(
          withdrawn
              ? {'page': 1, 'last_page': 1, 'items': []}
              : messageListFixture(),
        ),
        'GET v2/communication/messages/11': (_) => withdrawn
            ? const FakeResponse.notFound()
            : ok(messageReaderFixture(account: 21)),
        'POST v2/communication/messages/11/read': (_) {
          acknowledged = true;
          return ok({'id': 11, 'read_at': '2026-10-24T10:00:00Z'});
        },
        'GET v2/communication/messages/999': const FakeResponse.notFound(),
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
      await tapWhenReady(tester, find.text('School messages'));
      await pumpUntilFound(tester, find.byType(SchoolMessagesScreen));
      expect(find.text('New school message'), findsNothing);
      await tapWhenReady(tester, find.text('Private weekly update'));
      await pumpUntilFound(tester, find.text('Private school text.'));
      await pumpUntilFound(tester, find.text('Read acknowledgement saved'));
      expect(acknowledged, isTrue);
      withdrawn = true;
      await tapWhenReady(tester, find.text('Refresh message'));
      await pumpUntilFound(
        tester,
        find.text(
          'Could not load school messages. Check your connection and access, then try again.',
        ),
      );
      expect(find.text('Private school text.'), findsNothing);
      openDeepLink(
        const DeepLinkTarget(
          Routes.schoolMessages,
          arguments: MessageLinkTarget(999),
        ),
        signedIn: true,
      );
      await pumpUntilFound(
        tester,
        find.text(
          'Could not load school messages. Check your connection and access, then try again.',
        ),
      );
      expect(find.text('Private school text.'), findsNothing);
      final requests = api.requests
          .where((request) => request.uri.path.contains('/communication/'))
          .toList();
      expect(
        requests.every((request) => request.headers['X-School-Id'] == '1'),
        isTrue,
      );
      expect(
        requests.any((request) => request.uri.path.contains('/drafts')),
        isFalse,
      );
      expect(tester.takeException(), isNull);
    },
  );
}
