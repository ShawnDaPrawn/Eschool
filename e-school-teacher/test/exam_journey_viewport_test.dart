import 'package:dio/dio.dart';
import 'package:eschool_academics/eschool_academics.dart';
import 'package:eschool_academics/testing.dart';
import 'package:eschool_core/eschool_core.dart';
import 'package:eschool_core/testing.dart';
import 'package:flutter/material.dart';
import 'package:flutter_test/flutter_test.dart';

import '../integration_test/support/exam_journey.dart';

void main() {
  for (final size in [const Size(390, 600), const Size(411, 914)]) {
    testWidgets('exam journey checks confidence on phone viewport $size', (
      tester,
    ) async {
      tester.view.physicalSize = size;
      tester.view.devicePixelRatio = 1;
      addTearDown(tester.view.resetPhysicalSize);
      addTearDown(tester.view.resetDevicePixelRatio);
      final fixture = ExamCoachSmokeFixture(reader: 31);
      late final FakeApi api;
      api = FakeApi(routes: fixture.routes(() => api));
      final repository = ApiExamRepository(
        ApiClient(
          authToken: () => '',
          sendsBearerToken: false,
          onUnauthorized: () {},
          createDio: () => Dio()..httpClientAdapter = api,
        ),
        schoolId: 1,
        readerId: 31,
        contextActive: () => true,
      );
      await tester.pumpWidget(
        MaterialApp(home: ExamScreen(repository: repository, staff: true)),
      );
      await tester.pumpAndSettle();
      await runExamTeacher(tester, fixture);
      await tester.pumpWidget(const SizedBox.shrink());
    });
  }
}
