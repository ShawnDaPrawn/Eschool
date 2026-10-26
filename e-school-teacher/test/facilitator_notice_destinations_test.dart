import 'package:dio/dio.dart';
import 'package:eschool_core/testing.dart';
import 'package:eschool_session/eschool_session.dart';
import 'package:eschool_teacher/facilitator/facilitator_app.dart';
import 'package:eschool_teacher/facilitator/facilitator_repository.dart';
import 'package:flutter/material.dart';
import 'package:flutter_test/flutter_test.dart';
import 'package:hive/hive.dart';

import '../integration_test/support/staff_notice_fixture.dart';
import 'facilitator_test_fixture.dart';

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
  final repository = FacilitatorRepository(
    mode: AuthMode.bearer,
    createDio: () => Dio()..httpClientAdapter = api,
  );
  addTearDown(repository.dispose);
  await tester.pumpWidget(FacilitatorApp(repository: repository));
  await tester.pumpAndSettle();
  Finder field(String label) => find.byWidgetPredicate(
    (w) => w is TextField && w.decoration?.labelText == label,
  );
  await tester.enterText(field('School code'), 'jacaranda');
  await tester.enterText(field('Email or handle'), 'teacher@example.test');
  await tester.enterText(field('Password'), 'SyntheticStaff2026');
  await tap(tester, find.text('Sign in'));
  await tap(tester, find.text('Private staff updates'));
  return repository;
}

void main() {
  for (final width in [390.0, 1280.0]) {
    testWidgets(
      'Teacher progress inbox opens only current allocated academics at $width',
      (tester) async {
        final fixture = ProgressInboxFixture();
        final repository = await frame(tester, fixture.api, width);
        await tap(tester, find.text('Learning progress updated'));
        expect(fixture.marked, isTrue);
        await tap(tester, find.byKey(const ValueKey('learner-staff-9-1')));
        await tap(tester, find.text('Asha Synthetic').last);
        await tap(tester, find.text('Synthetic allocated Teacher').last);
        expect(
          find.text('Private allocated academic observation'),
          findsOneWidget,
        );
        expect(
          fixture.api.requests.where(
            (r) =>
                r.path.contains('/caseload') || r.path.contains('/wellbeing'),
          ),
          isEmpty,
        );
        expect(
          fixture.api.requests
              .where(
                (r) =>
                    r.path.contains('/growth/progress/') ||
                    r.path.contains('/staff/private-notices'),
              )
              .every(
                (r) =>
                    r.headers['X-School-Id'] == '1' &&
                    r.headers['X-Account-Id'] == '12',
              ),
          isTrue,
        );
        fixture.inboxGranted = false;
        await tester.runAsync(repository.refresh);
        await tester.pumpAndSettle();
        expect(
          find.text('Private allocated academic observation'),
          findsNothing,
        );
        expect(Hive.isBoxOpen('authBox'), isFalse);
        expect(tester.takeException(), isNull);
        await tester.pumpWidget(const SizedBox.shrink());
      },
    );
    testWidgets(
      'Discipline inbox action preserves source scope and erases revoked access at $width',
      (tester) async {
        final fixture = DisciplineInboxFixture();
        final repository = await frame(tester, fixture.api, width);
        await tap(tester, find.text('Discipline follow-up updated'));
        expect(fixture.marked, isTrue);
        await tap(tester, find.text('Synthetic learner'));
        expect(
          find.text('Private synthetic discipline follow-up'),
          findsOneWidget,
        );
        expect(
          fixture.api.requests
              .where(
                (r) =>
                    r.path.contains('/discipline/') ||
                    r.path.contains('/staff/private-notices'),
              )
              .every(
                (r) =>
                    r.headers['X-School-Id'] == '1' &&
                    r.headers['X-Account-Id'] == '12',
              ),
          isTrue,
        );
        fixture.available = false;
        await tester.runAsync(repository.refresh);
        await tester.pumpAndSettle();
        expect(
          find.text('Private synthetic discipline follow-up'),
          findsNothing,
        );
        expect(Hive.isBoxOpen('authBox'), isFalse);
        expect(tester.takeException(), isNull);
        await tester.pumpWidget(const SizedBox.shrink());
      },
    );
  }

  testWidgets(
    'A stale progress inbox item cannot open academic records after Teacher role removal',
    (tester) async {
      final fixture = ProgressInboxFixture(teacherRole: false);
      await frame(tester, fixture.api, 390);
      final tile = tester.widget<ListTile>(
        find
            .ancestor(
              of: find.text('Learning progress updated'),
              matching: find.byType(ListTile),
            )
            .first,
      );
      expect(tile.onTap, isNull);
      expect(
        fixture.api.requests.where((r) => r.path.contains('/growth/progress/')),
        isEmpty,
      );
      expect(fixture.marked, isFalse);
      await tester.pumpWidget(const SizedBox.shrink());
    },
  );
}
