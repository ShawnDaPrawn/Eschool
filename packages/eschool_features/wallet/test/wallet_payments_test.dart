import 'package:eschool_core/eschool_core.dart';
import 'package:eschool_wallet/eschool_wallet.dart';
import 'package:eschool_wallet/testing.dart';
import 'package:flutter/material.dart';
import 'package:flutter_test/flutter_test.dart';
import 'package:qr_flutter/qr_flutter.dart';
import 'package:shared_preferences/shared_preferences.dart';

void main() {
  setUp(() => SharedPreferences.setMockInitialValues({}));
  test(
    'a QR instruction preserves balances and validates the exact reviewed quote',
    () async {
      final server = WalletPaymentFakeServer();
      final shop = server.commerce.repository();
      await shop.walletChoices();
      final quote = await shop.quote(81, 6, {51: 1});
      final balances = Map<int, int>.from(server.commerce.balances);
      final repository = server.repository();
      final row = await repository.issue(
        quote,
        '00000000-0000-4000-8000-000000001802',
      );
      expect(row.amountCents, quote.amountCents);
      expect(server.commerce.balances, balances);
      final request = server.api.requests.lastWhere((r) => r.method == 'POST');
      expect(
        request.headers['Idempotency-Key'],
        '00000000-0000-4000-8000-000000001802',
      );
      expect(request.headers['X-School-Id'].toString(), '1');
      expect(request.headers['X-Account-Id'].toString(), '21');
      server.forgedActor = 999;
      await expectLater(repository.read(row.id), throwsA(isA<ApiException>()));
    },
  );
  test(
    'a lost creation response retains only a scoped UUID and recovers the original instruction',
    () async {
      final server = WalletPaymentFakeServer()..loseReply = true;
      final shop = server.commerce.repository();
      await shop.walletChoices();
      final quote = await shop.quote(81, 6, {51: 1});
      final cubit = WalletPaymentCubit(server.repository());
      await cubit.load(quote: quote);
      expect(await cubit.issue(), isFalse);
      expect(cubit.hasPending, isTrue);
      final prefs = await SharedPreferences.getInstance();
      expect(prefs.getKeys().length, 1);
      expect(prefs.getKeys().single, startsWith('wallet_pending_v1_1_21_'));
      expect(prefs.get(prefs.getKeys().single), isTrue);
      expect(await cubit.recover(), isTrue);
      expect(cubit.hasPending, isFalse);
      expect(cubit.state.page!.items.single.id, WalletPaymentFakeServer.id);
      expect(server.writes, 1);
      await cubit.close();
    },
  );
  test(
    'background and revoked live authority discard payment credentials',
    () async {
      final server = WalletPaymentFakeServer()..issued = true;
      final cubit = WalletPaymentCubit(server.repository());
      await cubit.load();
      await cubit.read(WalletPaymentFakeServer.id);
      expect(cubit.state.payment!.qr, isNotNull);
      cubit.suspend();
      expect(cubit.state.payment, isNull);
      expect(cubit.state.page, isNull);
      server.active = false;
      expect(await cubit.load(), isFalse);
      expect(cubit.state.accessLost, isTrue);
      await cubit.close();
    },
  );
  for (final width in [390.0, 820.0, 1280.0]) {
    testWidgets('private QR and paid outcome at $width pixels', (tester) async {
      tester.view.physicalSize = Size(width, 1100);
      tester.view.devicePixelRatio = 1;
      addTearDown(tester.view.resetPhysicalSize);
      addTearDown(tester.view.resetDevicePixelRatio);
      final server = WalletPaymentFakeServer()..issued = true;
      await tester.pumpWidget(
        MaterialApp(
          theme: AppTheme.light(),
          home: WalletPaymentScreen(repository: server.repository()),
        ),
      );
      await tester.pumpAndSettle();
      await tester.tap(find.byType(ListTile));
      await tester.pumpAndSettle();
      expect(find.byType(QrImageView), findsOneWidget);
      server.paid = true;
      await tester.ensureVisible(find.text('Check this payment outcome'));
      await tester.tap(find.text('Check this payment outcome'));
      await tester.pumpAndSettle();
      expect(find.byType(QrImageView), findsNothing);
      expect(find.text('Payment status: Paid'), findsOneWidget);
      expect(tester.takeException(), isNull);
      await tester.pumpWidget(const SizedBox.shrink());
    });
  }
}
