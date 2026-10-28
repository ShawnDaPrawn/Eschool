import 'package:dio/dio.dart';
import 'package:eschool_core/eschool_core.dart';
import 'package:eschool_core/testing.dart';
import 'package:eschool_fees/eschool_fees.dart';
import 'package:eschool_fees/testing.dart';
import 'package:flutter/material.dart';
import 'package:flutter_test/flutter_test.dart';

void main() {
  for (final width in [390.0, 820.0, 1280.0]) {
    testWidgets('parent sees the original one-off late invoice at $width', (
      tester,
    ) async {
      tester.view.physicalSize = Size(width, 1100);
      tester.view.devicePixelRatio = 1;
      addTearDown(tester.view.resetPhysicalSize);
      addTearDown(tester.view.resetDevicePixelRatio);
      final statement = feeLateChargeStatementFixture();
      final summary = Map<String, dynamic>.from(statement)
        ..remove('charges')
        ..remove('receipts')
        ..remove('credit_notes');
      Object answer(Object data) => {
        'error': false,
        'message': '',
        'code': 200,
        'data': data,
      };
      final api = FakeApi(
        routes: {
          'GET v2/parent/fees/accounts': answer({
            'school_id': 1,
            'holder_user_id': 21,
            'items': [summary],
            'current_page': 1,
            'per_page': 20,
            'total': 1,
          }),
          'GET v2/parent/fees/accounts/7/statement': answer(statement),
        },
      );
      final repository = ApiFeeStatementRepository(
        ApiClient(
          createDio: () => Dio()..httpClientAdapter = api,
          authToken: () => 'synthetic',
          onUnauthorized: () {},
        ),
        schoolId: 1,
        accountId: 21,
        contextActive: () => true,
      );
      await tester.pumpWidget(
        MaterialApp(
          theme: AppTheme.light(),
          home: FeeStatementScreen(repository: repository),
        ),
      );
      await tester.pumpAndSettle();
      await tester.tap(find.text('Open statement'));
      await tester.pumpAndSettle();
      await tester.scrollUntilVisible(
        find.text('One-off late charge for original overdue debt'),
        200,
      );
      expect(
        find.text('One-off late charge for original overdue debt'),
        findsOneWidget,
      );
      expect(find.textContaining('Private Finance'), findsNothing);
      expect(api.requests.every((r) => r.method == 'GET'), isTrue);
      expect(tester.takeException(), isNull);
    });
  }
}
