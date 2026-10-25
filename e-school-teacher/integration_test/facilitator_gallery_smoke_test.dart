import 'package:dio/dio.dart';
import 'package:eschool_session/eschool_session.dart';
import 'package:eschool_teacher/app/app.dart';
import 'package:eschool_teacher/facilitator/facilitator_repository.dart';
import 'package:flutter/material.dart';
import 'package:flutter_test/flutter_test.dart';
import 'package:integration_test/integration_test.dart';

import 'package:eschool_academics/testing.dart';

import 'support/facilitator_fixture.dart';
import 'support/smoke.dart' show pumpUntilFound, tapWhenReady;

void main() {
  IntegrationTestWidgetsFlutterBinding.ensureInitialized();
  testWidgets(
    'real facilitator opens private gallery then erases withdrawn access',
    (tester) async {
      final fixture = GalleryClientFixture(account: 12);
      Map<String, dynamic> profile() => {
        ...staffProfileFixture(),
        'features': {
          'groups': true,
          'messages': true,
          'gallery': fixture.granted,
        },
      };
      final reply = staffReplyFixture();
      (reply['data'] as Map)['profile'] = profile();
      final api = staffApi(
        signIn: reply,
        session: (_) => {'error': false, 'data': profile()},
      );
      api.routes.addAll(fixture.routes());
      final repo = FacilitatorRepository(
        mode: AuthMode.bearer,
        createDio: () => Dio()..httpClientAdapter = api,
      );
      await initializeApp(facilitatorRepository: repo);
      await pumpUntilFound(tester, find.text('Facilitator sign-in'));
      Finder field(String label) => find.byWidgetPredicate(
        (w) => w is TextField && w.decoration?.labelText == label,
      );
      await tester.enterText(field('School code'), 'jacaranda');
      await tester.enterText(field('Email or handle'), 'coach@example.test');
      await tester.enterText(field('Password'), 'SyntheticStaff2026');
      await tapWhenReady(tester, find.text('Sign in'));
      await tapWhenReady(tester, find.text('Private photos'));
      await pumpUntilFound(
        tester,
        find.textContaining('Private synthetic gallery caption'),
      );
      expect(
        api.requests
            .where((r) => r.path.contains('/gallery/'))
            .every(
              (r) =>
                  r.headers['X-School-Id'] == '1' &&
                  r.headers['X-Account-Id'] == '12',
            ),
        isTrue,
      );
      fixture.granted = false;
      await tester.runAsync(repo.refresh);
      await tester.pumpAndSettle();
      expect(
        find.textContaining('Private synthetic gallery caption'),
        findsNothing,
      );
      expect(find.text('Private photos'), findsNothing);
      expect(find.text('Activity groups'), findsOneWidget);
      expect(tester.takeException(), isNull);
      await tester.pumpWidget(const SizedBox.shrink());
      repo.dispose();
    },
  );
}
