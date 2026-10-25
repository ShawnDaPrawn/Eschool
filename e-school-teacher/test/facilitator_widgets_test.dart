import 'package:dio/dio.dart';
import 'package:eschool_core/testing.dart';
import 'package:eschool_session/eschool_session.dart';
import 'package:eschool_teacher/facilitator/facilitator_app.dart';
import 'package:eschool_teacher/facilitator/facilitator_repository.dart';
import 'package:flutter/material.dart';
import 'package:flutter_test/flutter_test.dart';
import 'package:hive/hive.dart';

import 'facilitator_test_fixture.dart';

Finder field(String label) => find.byWidgetPredicate(
  (w) => w is TextField && w.decoration?.labelText == label,
);
Future<void> fill(WidgetTester tester) async {
  await tester.enterText(field('School code'), 'jacaranda');
  await tester.enterText(field('Email or handle'), 'coach@example.test');
  await tester.enterText(field('Password'), 'SyntheticStaff2026');
}

Future<void> tap(WidgetTester tester, Finder finder) async {
  await tester.ensureVisible(finder);
  await tester.tap(finder);
  await tester.pumpAndSettle();
}

Future<FacilitatorRepository> frame(
  WidgetTester tester,
  FakeApi api,
  double width,
) async {
  staffTestLabels(tester);
  tester.view.physicalSize = Size(width, 900);
  tester.view.devicePixelRatio = 1;
  addTearDown(tester.view.resetPhysicalSize);
  addTearDown(tester.view.resetDevicePixelRatio);
  final repo = FacilitatorRepository(
    mode: AuthMode.bearer,
    createDio: () => Dio()..httpClientAdapter = api,
  );
  addTearDown(repo.dispose);
  await tester.pumpWidget(FacilitatorApp(repository: repo));
  await tester.pumpAndSettle();
  return repo;
}

void main() {
  for (final width in [390.0, 1280.0]) {
    testWidgets(
      'real staff sign-in opens scoped groups without teacher bootstrap at ${width.toInt()}px',
      (tester) async {
        final api = staffApi();
        final repo = await frame(tester, api, width);
        expect(api.requests, isEmpty);
        expect(Hive.isBoxOpen('authBox'), isFalse);
        await fill(tester);
        await tap(tester, find.text('Sign in'));
        expect(find.text('Synthetic Coach 12'), findsOneWidget);
        expect(find.text('Marks'), findsNothing);
        expect(find.text('Counselling'), findsNothing);
        await tap(tester, find.text('Activity groups'));
        expect(find.text('No current activity groups.'), findsOneWidget);
        expect(api.requests.last.headers['X-Account-Id'], '12');
        // Same IDs and unchanged feature flags must still dispose the private
        // route when fresh authorization starts, rather than keep cached rows.
        await tester.runAsync(repo.refresh);
        await tester.pumpAndSettle();
        expect(find.text('Your school activities'), findsOneWidget);
        expect(find.text('No current activity groups.'), findsNothing);
        await tap(tester, find.text('Activity groups'));
        expect(find.text('No current activity groups.'), findsOneWidget);
        expect(
          api.requests.where((r) => r.path.endsWith('/activities/groups')),
          hasLength(2),
        );
        expect(Hive.isBoxOpen('authBox'), isFalse);
        expect(tester.takeException(), isNull);
      },
    );
  }
  testWidgets(
    'policy/password setup appears and only accepted completion enters private shell',
    (tester) async {
      var attempts = 0;
      final api = staffApi(
        signIn: (RequestOptions r) => ++attempts == 1
            ? FakeResponse(staffSetupFixture(), status: 409)
            : staffReplyFixture(),
      );
      final repo = await frame(tester, api, 390);
      await fill(tester);
      await tap(tester, find.text('Sign in'));
      expect(repo.signedIn, isFalse);
      expect(
        find.text('Read this synthetic policy before accepting.'),
        findsOneWidget,
      );
      await tester.enterText(field('New password'), 'NewPrivateStaff2026');
      await tester.enterText(
        field('Confirm new password'),
        'NewPrivateStaff2026',
      );
      await tap(tester, find.byType(Checkbox));
      await tap(tester, find.text('Complete sign-in'));
      expect(repo.signedIn, isTrue);
      expect(find.text('Your school activities'), findsOneWidget);
      final body = api.requests.last.data as Map;
      expect(body['acceptances'], {'navigateed': 'a' * 64});
      expect(body['new_password'], 'NewPrivateStaff2026');
      expect(tester.takeException(), isNull);
    },
  );
  testWidgets('wrong-role refusal cannot expose teacher or group data', (
    tester,
  ) async {
    final api = staffApi(
      signIn: const FakeResponse({
        'error': true,
        'message': 'No current staff assignment.',
      }, status: 403),
    );
    final repo = await frame(tester, api, 390);
    await fill(tester);
    await tap(tester, find.text('Sign in'));
    expect(repo.signedIn, isFalse);
    expect(find.text('No current staff assignment.'), findsOneWidget);
    expect(find.text('Activity groups'), findsNothing);
    expect(Hive.isBoxOpen('authBox'), isFalse);
  });
}
