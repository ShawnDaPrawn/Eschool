import 'package:dio/dio.dart';
import 'package:eschool_academics/testing.dart';
import 'package:eschool_core/eschool_core.dart';
import 'package:eschool_core/testing.dart';
import 'package:eschool_wallet/testing.dart';
import 'package:flutter_test/flutter_test.dart';
import 'package:integration_test/integration_test.dart';

import 'support/fixtures.dart';
import 'support/smoke.dart';

void main() {
  IntegrationTestWidgetsFlutterBinding.ensureInitialized();
  testWidgets('teacher reads own wallet without fee permissions', (
    tester,
  ) async {
    var allowed = true;
    Object response(Object data) => allowed
        ? ok(data)
        : FakeResponse({
            'error': true,
            'message': 'Forbidden',
            'code': 403,
          }, status: 403);
    final api = await startSmokeApp(tester, {
      ...teacherRoutes(),
      'GET v2/me/context': ok({
        ...academicContextFixture(),
        'user': {
          'id': 12,
          'first_name': 'Synthetic',
          'last_name': 'Teacher',
          'name': 'Synthetic Teacher',
          'email': teacherEmail,
          'image': null,
          'roles': ['Teacher'],
        },
        'feature_flags': {'module_switches': true},
        'modules': [
          for (final key in [
            'school',
            'people',
            'academics',
            'wallet',
            'web_teacher',
          ])
            {'key': key, 'read_only': false},
        ],
      }),
      'GET v2/wallets': (RequestOptions _) =>
          response(walletIndexFixture(viewer: 12)),
      'GET v2/wallets/6/statement': (RequestOptions _) =>
          response(walletStatementFixture(owner: 12, viewer: 12)),
      'GET v2/wallets/6/desk-topups': (RequestOptions _) =>
          response(walletDeskReceiptsFixture(owner: 12, viewer: 12)),
    });
    await pumpUntilFound(tester, find.text('Email or handle'));
    await enterField(tester, 0, teacherEmail);
    await enterField(tester, 1, password);
    await tapWhenReady(
      tester,
      find.widgetWithText(CustomRoundedButton, 'Sign in'),
    );
    await tapWhenReady(tester, find.text('Wallets'));
    await tapWhenReady(tester, find.text('Open wallet statement'));
    await pumpUntilFound(tester, find.text('Wallet balance'));
    expect(find.text('Staff Synthetic'), findsOneWidget);
    expect(find.text('Edit spending controls'), findsOneWidget);
    await tapWhenReady(tester, find.text('View desk top-up receipts'));
    await pumpUntilFound(tester, find.text('WT-201'));
    expect(find.text('Awaiting independent approval'), findsOneWidget);
    allowed = false;
    await tester.tap(find.byTooltip('Refresh wallets'));
    await pumpUntilFound(
      tester,
      find.text('Your wallet access changed. Reopen Wallets.'),
    );
    expect(find.text('Staff Synthetic'), findsNothing);
    expect(find.text('WT-201'), findsNothing);
    expect(
      api.requests
          .where((q) => q.uri.path.contains('/v2/wallets'))
          .every((q) => q.headers['X-Account-Id'].toString() == '12'),
      true,
    );
    expect(tester.takeException(), isNull);
  });
}
