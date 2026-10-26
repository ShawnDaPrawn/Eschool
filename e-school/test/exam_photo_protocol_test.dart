import 'dart:convert';

import 'package:dio/dio.dart';
import 'package:eschool_academics/eschool_academics.dart';
import 'package:eschool_academics/testing.dart';
import 'package:eschool_core/eschool_core.dart';
import 'package:eschool_core/testing.dart';
import 'package:flutter/material.dart';
import 'package:flutter_test/flutter_test.dart';

Future<void> flushPhoto(WidgetTester tester) async {
  for (var i = 0; i < 20; i++) {
    await tester.runAsync(
      () => Future<void>.delayed(const Duration(milliseconds: 20)),
    );
    await tester.pump();
  }
}

Future<void> tapPhoto(WidgetTester tester, Finder target) async {
  tester
      .state<ScrollableState>(find.byType(Scrollable).first)
      .position
      .jumpTo(0);
  await tester.pump();
  if (target.evaluate().isEmpty) {
    await tester.scrollUntilVisible(
      target,
      220,
      scrollable: find.byType(Scrollable).first,
    );
  }
  await tester.ensureVisible(target);
  await tester.pump();
  expect(target.hitTestable(), findsOneWidget);
  await tester.runAsync(() => tester.tap(target.hitTestable()));
  await flushPhoto(tester);
}

void main() {
  testWidgets(
    'Student approved crop reaches the exact multipart upload and feedback reader',
    (tester) async {
      tester.view.physicalSize = const Size(390, 844);
      tester.view.devicePixelRatio = 1;
      addTearDown(tester.view.resetPhysicalSize);
      addTearDown(tester.view.resetDevicePixelRatio);
      tester.binding.handleAppLifecycleStateChanged(AppLifecycleState.resumed);
      final fixture = ExamCoachSmokeFixture(reader: 11, staff: false);
      late final FakeApi api;
      api = FakeApi(routes: fixture.routes(() => api));
      final client = ApiClient(
        authToken: () => '',
        sendsBearerToken: false,
        onUnauthorized: () {},
        createDio: () => Dio()..httpClientAdapter = api,
      );
      await tester.pumpWidget(
        MaterialApp(
          home: ExamScreen(
            repository: ApiExamRepository(
              client,
              schoolId: 1,
              readerId: 11,
              contextActive: () => true,
            ),
            staff: false,
            selectPhoto: () async => base64Decode(ExamCoachSmokeFixture.pixels),
          ),
        ),
      );
      await flushPhoto(tester);
      await tapPhoto(tester, find.text('Synthetic licensed paper'));
      await tapPhoto(tester, find.text('Choose working photo'));
      await tapPhoto(
        tester,
        find.text(
          'Share this cropped working and provisional feedback with my allocated teacher.',
        ),
      );
      await tapPhoto(
        tester,
        find.text('Select the whole photo if it contains only working'),
      );
      await tapPhoto(
        tester,
        find.text('I checked this selection contains only my working.'),
      );
      expect(
        tester
            .widget<FilledButton>(
              find.ancestor(
                of: find.text('Use this working crop'),
                matching: find.byType(FilledButton),
              ),
            )
            .onPressed,
        isNotNull,
      );
      await tapPhoto(tester, find.text('Use this working crop'));
      expect(
        fixture.uploaded,
        true,
        reason:
            'busy=${find.byType(LinearProgressIndicator).evaluate().length}; ${api.requestedPaths.join('\n')}\n${tester.widgetList<Text>(find.byType(Text)).map((t) => t.data).join(' | ')}',
      );
      await tapPhoto(tester, find.text('Request provisional feedback'));
      expect(fixture.answerState, 'completed');
      expect(api.requestedPaths.any((p) => p.contains('/staff/')), false);
      expect(tester.takeException(), isNull);
      await tester.pumpWidget(const SizedBox.shrink());
    },
  );
}
