import 'dart:convert';

import 'package:crypto/crypto.dart';
import 'package:dio/dio.dart';
import 'package:eschool/ui/screens/parentHomeScreen.dart';
import 'package:eschool/ui/screens/home/homeScreen.dart';
import 'package:eschool_academics/testing.dart';
import 'package:eschool_core/eschool_core.dart';
import 'package:eschool_core/testing.dart';
import 'package:flutter_test/flutter_test.dart';
import 'package:flutter/material.dart';
import 'package:integration_test/integration_test.dart';

import 'support/fixtures.dart';
import 'support/smoke.dart';

void main() {
  IntegrationTestWidgetsFlutterBinding.ensureInitialized();
  for (final parentProfile in [false, true]) {
    testWidgets(
      '${parentProfile ? 'parent' : 'learner'} receives a real live home counter and clears revoked recognition',
      (tester) async {
        final errors = <String>[];
        final original = FlutterError.onError;
        FlutterError.onError = (details) {
          errors.add(details.toString());
          IntegrationTestWidgetsFlutterBinding.ensureInitialized().reportData =
              {'flutter_errors': errors};
          original?.call(details);
        };
        addTearDown(() => FlutterError.onError = original);
        var revoked = false, awarded = false;
        final account = parentProfile ? 21 : 11;
        final api = await startSmokeApp(tester, {
          ...(parentProfile ? parentOfTwoRoutes() : learnerRoutes()),
          'GET v2/me/context': ok({
            ...academicContextFixture(
              role: parentProfile ? 'Parent' : 'Student',
            ),
            'user': {
              'id': account,
              'first_name': 'Synthetic',
              'last_name': 'Mokoena',
              'name': 'Synthetic Mokoena',
              'email': parentProfile ? parentEmail : 'learner@example.test',
              'image': null,
              'roles': [parentProfile ? 'Parent' : 'Student'],
            },
            'feature_flags': {'module_switches': true, 'mastery': true},
            'modules': [
              for (final key in [
                'school',
                'people',
                'academics',
                'mastery',
                'house_points',
                'live_updates',
                'awards',
                'web_parent',
                'web_learner',
              ])
                {'key': key, 'read_only': false},
            ],
            if (parentProfile)
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
          'POST v2/live/session': (_) => ok({
            'lease_id': '75133b39-0476-4ba0-a627-a152d042687a',
            'channel':
                'private-ne.school.1.user.$account.session.75133b39-0476-4ba0-a627-a152d042687a',
            'key': 'qualification-browser-key',
            'host': '127.0.0.1',
            'port': 8088,
            'scheme': 'ws',
            'heartbeat_seconds': 20,
            'expires_in': 45,
          }),
          'POST v2/live/authorize': (RequestOptions request) {
            final body = Map<String, dynamic>.from(request.data as Map);
            final signature =
                Hmac(
                  sha256,
                  utf8.encode('qualification-browser-secret-32-characters'),
                ).convert(
                  utf8.encode('${body['socket_id']}:${body['channel_name']}'),
                );
            return ok({'auth': 'qualification-browser-key:$signature'});
          },
          for (final module in ['house_points', 'awards'])
            'GET v2/recognition/$module/access': (_) => revoked
                ? const FakeResponse({'message': 'Withdrawn'}, status: 403)
                : ok({
                    'module': module,
                    'school_id': 1,
                    'account_id': account,
                    'session_year_id': 7,
                    'today': '2026-10-25',
                    'family': true,
                    'can_write': false,
                    'manager': false,
                    'grade_ids': [],
                  }),
          'GET v2/recognition/house_points/counters': (_) => ok({
            'session_year_id': 7,
            'revision': awarded ? 2 : 1,
            'items': [
              {
                'id': 3,
                'name': 'Mountain',
                'emblem': 'mountain',
                'primary_colour': '#315b8c',
                'secondary_colour': '#ffffff',
                'active': true,
                'revision': 1,
                'points': awarded ? 10 : 5,
              },
            ],
          }),
          'GET v2/recognition/house_points/learners/11/points': (_) => ok({
            'items': [
              if (awarded)
                {
                  'id': 2,
                  'house_id': 3,
                  'house_name': 'Mountain',
                  'category_name': 'Helping',
                  'points': 5,
                  'reason': 'Private new live evidence',
                  'on_date': '2026-10-25',
                  'public': false,
                  'reverses_id': null,
                },
              {
                'id': 1,
                'house_id': 3,
                'house_name': 'Mountain',
                'category_name': 'Helping',
                'points': 5,
                'reason': 'Private family recognition evidence',
                'on_date': '2026-10-25',
                'public': false,
                'reverses_id': null,
              },
            ],
            'next_before': null,
          }),
          'GET v2/recognition/awards/learners/11/awards': ok({
            'items': [
              {
                'id': 9,
                'session_year_id': 7,
                'title': 'Synthetic service award',
                'criteria': 'Service achievement',
                'presented_on': '2026-10-25',
                'event': 'Assembly',
                'outside_organisation': null,
                'source_kind': 'catalogue',
              },
            ],
            'next_before': null,
          }),
        });
        await tapWhenReady(
          tester,
          find.text(parentProfile ? 'Login as Parent' : 'Login as Student'),
        );
        await pumpUntilFound(
          tester,
          find.text(
            parentProfile
                ? 'Email or handle'
                : 'Student number, GR number or handle',
          ),
        );
        await enterField(tester, 0, parentProfile ? parentEmail : grNumber);
        await enterField(tester, 1, password);
        await tapWhenReady(
          tester,
          find.widgetWithText(CustomRoundedButton, 'Sign in'),
        );
        await pumpUntilFound(
          tester,
          find.byType(parentProfile ? ParentHomeScreen : HomeScreen),
        );

        await pumpUntilFound(
          tester,
          find.byKey(const ValueKey('home-house-3')),
        );
        expect(find.text('5 house points'), findsOneWidget);
        // Wait for the actual private socket subscription before dispatching a
        // loopback test event. Domain queue/source checks are qualified by PHP.
        final ready = DateTime.now().add(const Duration(seconds: 10));
        while (!api.requested('POST v2/live/authorize') &&
            DateTime.now().isBefore(ready)) {
          await tester.pump(const Duration(milliseconds: 100));
        }
        expect(api.requested('POST v2/live/authorize'), true);
        await tester.pump(const Duration(milliseconds: 300));
        awarded = true;
        final watch = Stopwatch()..start();
        await Dio().get(
          'http://127.0.0.1:8089/?type=house&account=$account&revision=2',
        );
        await pumpUntilFound(tester, find.text('10 house points'));
        watch.stop();
        expect(watch.elapsed, lessThan(const Duration(seconds: 2)));
        await pumpUntilFound(tester, find.text('5 house points · Mountain'));
        expect(find.text('Private new live evidence'), findsNothing);
        await tapWhenReady(tester, find.text('Houses and awards'));
        await pumpUntilFound(
          tester,
          find.textContaining('Private family recognition evidence'),
        );
        expect(find.text('Award house points'), findsNothing);
        await tapWhenReady(
          tester,
          find.byType(DropdownButtonFormField<String>),
        );
        await tester.pumpAndSettle();
        await tapWhenReady(tester, find.text('Permanent awards').last);
        await pumpUntilFound(tester, find.text('Synthetic service award'));
        expect(
          find.textContaining('Private family recognition evidence'),
          findsNothing,
        );
        revoked = true;
        tester.binding.handleAppLifecycleStateChanged(
          AppLifecycleState.inactive,
        );
        await tester.pump();
        expect(find.text('Synthetic service award'), findsNothing);
        tester.binding.handleAppLifecycleStateChanged(
          AppLifecycleState.resumed,
        );
        await pumpUntilFound(
          tester,
          find.text('Your recognition access changed. Reopen this screen.'),
        );
        expect(find.text('Synthetic service award'), findsNothing);
        final requests = api.requests
            .where((r) => r.uri.path.contains('/recognition/'))
            .toList();
        expect(requests, isNotEmpty);
        expect(
          requests.every(
            (r) =>
                r.headers['X-School-Id'] == '1' &&
                r.headers['X-Account-Id'] == '$account',
          ),
          isTrue,
        );
        expect(
          requests.any((r) => r.uri.path.contains('/learners/12')),
          isFalse,
        );
        expect(tester.takeException(), isNull);
      },
    );
  }
}
