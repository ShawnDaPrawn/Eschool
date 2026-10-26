import 'package:dio/dio.dart';
import 'package:eschool_academics/eschool_academics.dart';
import 'package:eschool_core/eschool_core.dart';
import 'package:eschool_core/testing.dart';
import 'package:flutter/material.dart';
import 'package:flutter_test/flutter_test.dart';

import '../integration_test/support/teaching_fixture.dart';

void main() {
  testWidgets(
    'saved teaching definition remains discoverable after a narrow mobile IME scroll',
    (tester) async {
      tester.view.physicalSize = const Size(390, 844);
      tester.view.devicePixelRatio = 1;
      tester.view.viewInsets = const FakeViewPadding(bottom: 300);
      addTearDown(tester.view.resetPhysicalSize);
      addTearDown(tester.view.resetDevicePixelRatio);
      addTearDown(tester.view.resetViewInsets);
      tester.binding.handleAppLifecycleStateChanged(AppLifecycleState.resumed);
      final fixture = TeachingSmokeFixture();
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
          home: TeachingScreen(
            repository: ApiTeachingRepository(
              client,
              schoolId: 1,
              readerId: 31,
              contextActive: () => true,
            ),
          ),
        ),
      );
      await tester.pumpAndSettle();
      await runTeachingApproval(tester, fixture);
      expect(
        fixture.current!['definition']['title'],
        'Teacher reviewed browser worksheet',
      );
      expect(fixture.effects, 2);
      expect(fixture.current!['state'], 'approved');
      await tester.pumpWidget(const SizedBox.shrink());
    },
  );
}
