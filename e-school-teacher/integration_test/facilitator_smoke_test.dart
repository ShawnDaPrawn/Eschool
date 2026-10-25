import 'package:dio/dio.dart';
import 'package:eschool_session/eschool_session.dart';
import 'package:eschool_teacher/app/app.dart';
import 'package:eschool_teacher/app/staffBuildIdentity.dart';
import 'package:eschool_teacher/facilitator/facilitator_repository.dart';
import 'package:flutter/material.dart';
import 'package:flutter_test/flutter_test.dart';
import 'package:hive/hive.dart';
import 'package:integration_test/integration_test.dart';

import 'support/facilitator_fixture.dart';
import 'support/smoke.dart' show pumpUntilFound, tapWhenReady;

void main() {
  IntegrationTestWidgetsFlutterBinding.ensureInitialized();
  testWidgets(
    'real facilitator entrypoint signs in, scopes groups and signs out',
    (tester) async {
      expect(StaffBuildIdentity.current.canStartFacilitator, isTrue);
      final api = staffApi();
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
      await pumpUntilFound(tester, find.text('Your school activities'));
      expect(find.text('Synthetic Coach 12'), findsOneWidget);
      expect(find.text('Marks'), findsNothing);
      expect(Hive.isBoxOpen('authBox'), isFalse);
      await tapWhenReady(tester, find.text('Activity groups'));
      await pumpUntilFound(tester, find.text('No current activity groups.'));
      expect(api.requests.last.headers['X-Account-Id'], '12');
      await tester.pageBack();
      await pumpUntilFound(tester, find.text('Your school activities'));
      await tapWhenReady(tester, find.byTooltip('Sign out'));
      await pumpUntilFound(tester, find.text('Facilitator sign-in'));
      expect(repo.signedIn, isFalse);
      expect(Hive.isBoxOpen('authBox'), isFalse);
      expect(tester.takeException(), isNull);
      await tester.pumpWidget(const SizedBox.shrink());
      repo.dispose();
    },
  );
}
