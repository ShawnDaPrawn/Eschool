import 'package:dio/dio.dart';
import 'package:eschool_core/eschool_core.dart';
import 'package:eschool_session/eschool_session.dart';
import 'package:eschool_teacher/vendor/vendor_app.dart';
import 'package:eschool_teacher/vendor/vendor_nfc.dart';
import 'package:eschool_teacher/vendor/vendor_repository.dart';
import 'package:flutter/material.dart';
import 'package:flutter_test/flutter_test.dart';
import 'package:shared_preferences/shared_preferences.dart';

import '../integration_test/support/vendor_payment_fixture.dart';
import 'vendor_widgets_test.dart' show vendorLabels, login;

VendorRepository paymentRepository(VendorPaymentFixture fixture) =>
    VendorRepository(
      mode: AuthMode.bearer,
      createDio: () => Dio()..httpClientAdapter = VendorPaymentAdapter(fixture),
    );
Map<String, dynamic> signIn() => {
  'school': 'jacaranda',
  'email': 'vendor@example.test',
  'password': 'synthetic',
};
const tap = VendorSunTap(
  cardId: '00000000-0000-4000-8000-000000001904',
  picc: 'EF963FF7828658A599F3041510671E88',
  mac: '94EED9EE65337086',
);
Future<void> press(WidgetTester tester, String text) async {
  final finder = find.text(text);
  if (finder.evaluate().isEmpty) {
    await tester.scrollUntilVisible(
      finder,
      180,
      scrollable: find.byType(Scrollable).last,
    );
  }
  await tester.ensureVisible(finder.first);
  await tester.tap(finder.first);
  await tester.pumpAndSettle();
}

void main() {
  TestWidgetsFlutterBinding.ensureInitialized();
  setUp(() => SharedPreferences.setMockInitialValues({}));
  test(
    'a wallet QR confirms one original payment and keeps collection separate',
    () async {
      final fake = VendorPaymentFixture(),
          repo = paymentRepository(VendorPaymentFixture());
      repo.dispose();
      final actual = paymentRepository(fake);
      await actual.signIn(signIn());
      expect(actual.profile!.walletPaymentsAvailable, isTrue);
      expect(
        await actual.payments.lookup('navigateed:wallet-pay:${'a' * 64}'),
        isTrue,
      );
      expect(actual.payments.review!.instruction.amountCents, 1250);
      expect(await actual.payments.pay(photoConfirmed: false), isTrue);
      expect(actual.payments.receipt!.number, 'VO-001905');
      expect(fake.paymentWrites, 1);
      expect(fake.writes, 0);
      expect(
        actual.message,
        'Payment recorded. Hand over the listed goods once.',
      );
      expect(await actual.payments.pay(photoConfirmed: false), isFalse);
      actual.dispose();
    },
  );
  test('a lost payment response persists only its counter UUID and recovers without another debit', () async {
    final fake = VendorPaymentFixture()..losePaymentReply = true;
    final repo = paymentRepository(fake);
    await repo.signIn(signIn());
    await repo.payments.lookup('navigateed:wallet-pay:${'a' * 64}');
    expect(await repo.payments.pay(photoConfirmed: false), isFalse);
    expect(repo.hasPendingPayment, isTrue);
    final prefs = await SharedPreferences.getInstance();
    final pending = prefs.getStringList('vendor-payment-pending:1:71')!;
    expect(pending.length, 1);
    expect(pending.single, startsWith('81:'));
    expect(pending.join(), isNot(contains('wallet-pay:')));
    expect(pending.join(), isNot(contains('NED1')));
    repo.dispose();
    final restored = paymentRepository(fake);
    await restored.signIn(signIn());
    expect(restored.hasPendingPayment, isTrue);
    expect(await restored.recover(), isTrue);
    expect(restored.hasPending, isFalse);
    expect(restored.payments.receipt!.status, 'collected');
    expect(restored.message, contains('do not hand over goods again'));
    expect(fake.paymentWrites, 1);
    restored.dispose();
  });
  test('a malformed payment acknowledgment preserves its recoverable original UUID', () async {
    final fake = VendorPaymentFixture()..malformedReplay = true;
    final repo = paymentRepository(fake);
    await repo.signIn(signIn());
    await repo.payments.lookup('navigateed:wallet-pay:${'a' * 64}');
    expect(await repo.payments.pay(photoConfirmed: false), isFalse);
    expect(repo.hasPendingPayment, isTrue);
    expect(await repo.recover(), isTrue);
    expect(repo.hasPending, isFalse);
    expect(fake.paymentWrites, 1);
    repo.dispose();
  });
  test('SUN displays a current private photo and requires explicit photo confirmation', () async {
    final fake = VendorPaymentFixture(),
        repo = paymentRepository(VendorPaymentFixture());
    repo.dispose();
    final actual = paymentRepository(fake);
    await actual.signIn(signIn());
    expect(actual.payments.pair(VendorPaymentFixture.readerCredential), isTrue);
    expect(await actual.payments.prepare(tap, {51: 1}), isTrue);
    expect(actual.payments.review!.holderName, 'Asha Synthetic');
    expect(actual.payments.portrait, isNotEmpty);
    expect(await actual.payments.pay(photoConfirmed: false), isFalse);
    expect(await actual.payments.pay(photoConfirmed: true), isTrue);
    expect(fake.paymentWrites, 1);
    final requests = fake.api.requests.where(
      (r) => r.uri.path.endsWith('/payments/card'),
    );
    expect(
      requests.single.headers['X-Payment-Reader'],
      VendorPaymentFixture.readerCredential,
    );
    final prefs = await SharedPreferences.getInstance();
    for (final key in prefs.getKeys()) {
      expect(prefs.get(key).toString(), isNot(contains('NED1')));
      expect(prefs.get(key).toString(), isNot(contains('Asha')));
    }
    actual.dispose();
  });
  test('background clears the photo and reader; revoked authority clears the private counter', () async {
    final fake = VendorPaymentFixture(),
        repo = paymentRepository(VendorPaymentFixture());
    repo.dispose();
    final actual = paymentRepository(fake);
    await actual.signIn(signIn());
    actual.payments.pair(VendorPaymentFixture.readerCredential);
    await actual.payments.prepare(tap, {51: 1});
    actual.suspend();
    expect(actual.payments.portrait, isNull);
    expect(actual.payments.review, isNull);
    expect(actual.payments.paired, isFalse);
    await actual.resume();
    fake.allowed = false;
    expect(
      await actual.payments.lookup('navigateed:wallet-pay:${'a' * 64}'),
      isFalse,
    );
    expect(actual.signedIn, isFalse);
    actual.dispose();
  });
  test('the NFC URI reader rejects another school, duplicate fields and a wrong origin', () {
    final base = Uri.parse(AppConfig.apiBaseUrl);
    final uri = Uri(
      scheme: 'https',
      host: base.host,
      port: base.port,
      path: '/card-tap',
      fragment: 's=1&c=${tap.cardId}&e=${tap.picc}&m=${tap.mac}',
    );
    expect(VendorSunTap.fromUri(uri.toString(), 1).picc, tap.picc);
    for (final value in [
      uri.toString().replaceFirst('s=1', 's=2'),
      '${uri.toString()}&s=1',
      uri.replace(host: 'wrong.example.test').toString(),
      uri.replace(query: 'wallet_balance=50000').toString(),
    ]) {
      expect(() => VendorSunTap.fromUri(value, 1), throwsFormatException);
    }
  });
  for (final width in [390.0, 820.0, 1280.0]) {
    testWidgets(
      'Vendor SUN photo review and exact confirmation at $width pixels',
      (tester) async {
        vendorLabels(tester);
        tester.view.physicalSize = Size(width, 1200);
        tester.view.devicePixelRatio = 1;
        addTearDown(tester.view.resetPhysicalSize);
        addTearDown(tester.view.resetDevicePixelRatio);
        final fake = VendorPaymentFixture(),
            repo = paymentRepository(VendorPaymentFixture());
        repo.dispose();
        final actual = paymentRepository(fake);
        await tester.pumpWidget(
          VendorApp(repository: actual, readCard: (_) async => tap),
        );
        await tester.pumpAndSettle();
        await login(tester);
        await press(tester, 'Wallet payments');
        final reader = find.byWidgetPredicate(
          (w) =>
              w is TextField &&
              w.decoration?.labelText == 'Private reader pairing credential',
        );
        await tester.ensureVisible(reader);
        await tester.enterText(reader, VendorPaymentFixture.readerCredential);
        await press(tester, 'Pair payment reader');
        final quantity = find.byType(DropdownButtonFormField<int>);
        await tester.ensureVisible(quantity);
        await tester.tap(quantity);
        await tester.pumpAndSettle();
        await tester.tap(find.text('1').last);
        await tester.pumpAndSettle();
        await press(tester, 'Tap school card for these goods');
        expect(find.text('Asha Synthetic'), findsOneWidget);
        expect(find.byType(Image), findsOneWidget);
        expect(fake.paymentWrites, 0);
        await press(tester, 'Confirm wallet payment and handover');
        expect(fake.paymentWrites, 0);
        await press(
          tester,
          'I checked this current photo, the exact items and total, and will hand over these goods once.',
        );
        await press(tester, 'Confirm wallet payment and handover');
        expect(fake.paymentWrites, 1);
        expect(find.byType(Image), findsNothing);
        expect(tester.takeException(), isNull);
        await tester.pumpWidget(const SizedBox.shrink());
        actual.dispose();
      },
    );
    testWidgets('Vendor QR payment and one handover at $width pixels', (
      tester,
    ) async {
      vendorLabels(tester);
      tester.view.physicalSize = Size(width, 1200);
      tester.view.devicePixelRatio = 1;
      addTearDown(tester.view.resetPhysicalSize);
      addTearDown(tester.view.resetDevicePixelRatio);
      final fake = VendorPaymentFixture(),
          repo = paymentRepository(VendorPaymentFixture());
      repo.dispose();
      final actual = paymentRepository(fake);
      await tester.pumpWidget(
        VendorApp(
          repository: actual,
          scanQr: (_) async => 'navigateed:wallet-pay:${'a' * 64}',
        ),
      );
      await tester.pumpAndSettle();
      await login(tester);
      await press(tester, 'Wallet payments');
      await press(tester, 'Scan wallet payment QR');
      expect(find.text('Review this counter payment'), findsOneWidget);
      await press(
        tester,
        'I checked the exact items and total and will hand over these goods once.',
      );
      await press(tester, 'Confirm wallet payment and handover');
      expect(fake.paymentWrites, 1);
      expect(
        find.text('Payment recorded. Hand over the listed goods once.'),
        findsOneWidget,
      );
      expect(find.text('Confirm wallet payment and handover'), findsNothing);
      expect(tester.takeException(), isNull);
      await tester.pumpWidget(const SizedBox.shrink());
      actual.dispose();
    });
  }
}
