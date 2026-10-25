import 'package:dio/dio.dart';
import 'package:eschool_academics/eschool_academics.dart';
import 'package:eschool_session/eschool_session.dart';
import 'package:eschool_teacher/app/app.dart';
import 'package:eschool_teacher/app/staffBuildIdentity.dart';
import 'package:eschool_teacher/facilitator/facilitator_repository.dart';
import 'package:flutter/material.dart';
import 'package:flutter_test/flutter_test.dart';
import 'package:hive/hive.dart';
import 'package:integration_test/integration_test.dart';

import 'support/notes_fixture.dart';
import 'support/smoke.dart' show pumpUntilFound, tapWhenReady;

void main() {
  IntegrationTestWidgetsFlutterBinding.ensureInitialized();
  testWidgets(
    'real counsellor shell reads notes and clears withdrawn private text',
    (tester) async {
      expect(StaffBuildIdentity.current.canStartFacilitator, isTrue);
      final fixture = NotesStaffFixture();
      final repo = FacilitatorRepository(
        mode: AuthMode.bearer,
        createDio: () => Dio()..httpClientAdapter = fixture.api,
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
      expect(find.text('Support plans'), findsOneWidget);
      await tapWhenReady(tester, find.text('Restricted counselling notes'));
      final chooser = find.descendant(
        of: find.byType(ProgressNotesScreen),
        matching: find.byType(DropdownButtonFormField<int>),
      );
      await tapWhenReady(tester, chooser);
      await tester.pumpAndSettle();
      await tester.tap(find.text('Synthetic learner').last);
      await tester.pumpAndSettle();
      await tapWhenReady(tester, find.text('Synthetic Counsellor'));
      await pumpUntilFound(
        tester,
        find.text('Private synthetic clinical observation'),
      );
      expect(
        fixture.api.requests
            .where((r) => r.path.contains('/growth/progress/'))
            .every(
              (r) =>
                  r.headers['X-Account-Id'] == '12' &&
                  r.headers['X-School-Id'] == '1',
            ),
        isTrue,
      );
      expect(Hive.isBoxOpen('authBox'), isFalse);
      fixture.granted = false;
      await tester.runAsync(repo.refresh);
      await tester.pumpAndSettle();
      expect(find.text('Private synthetic clinical observation'), findsNothing);
      await tapWhenReady(tester, find.text('Restricted counselling notes'));
      await pumpUntilFound(tester, find.text('Progress and notes'));
      expect(find.text('Synthetic learner'), findsNothing);
      expect(find.text('Private synthetic clinical observation'), findsNothing);
      expect(Hive.isBoxOpen('authBox'), isFalse);
      expect(tester.takeException(), isNull);
      await tester.pumpWidget(const SizedBox.shrink());
      repo.dispose();
    },
  );
}
