import 'dart:convert';

import 'package:dio/dio.dart';
import 'package:eschool_session/eschool_session.dart';
import 'package:eschool_teacher/vendor/vendor_app.dart';
import 'package:eschool_teacher/vendor/vendor_repository.dart';
import 'package:flutter/material.dart';
import 'package:flutter/services.dart';
import 'package:flutter_test/flutter_test.dart';
import 'package:shared_preferences/shared_preferences.dart';

import '../integration_test/support/vendor_fixture.dart';

void vendorLabels(WidgetTester tester) {
  final messenger = tester.binding.defaultBinaryMessenger;
  rootBundle.evict('assets/languages/en.json');
  messenger.setMockMessageHandler('flutter/assets', (message) async {
    if (utf8.decode(message!.buffer.asUint8List()) ==
        'assets/languages/en.json')
      return ByteData.sublistView(utf8.encode('{}'));
    return messenger.delegate.send('flutter/assets', message);
  });
  addTearDown(() {
    messenger.setMockMessageHandler('flutter/assets', null);
    rootBundle.evict('assets/languages/en.json');
  });
}

Future<void> login(WidgetTester tester) async {
  for (final pair in [
    (0, 'jacaranda'),
    (1, 'vendor@example.test'),
    (2, 'SyntheticVendor2026'),
  ])
    await tester.enterText(find.byType(TextFormField).at(pair.$1), pair.$2);
  await tester.ensureVisible(find.text('Sign in to vendor counter'));
  await tester.tap(find.text('Sign in to vendor counter'));
  await tester.pumpAndSettle();
}

Future<void> tap(WidgetTester tester, String label) async {
  final finder = find.text(label);
  if (finder.evaluate().isEmpty) {
    await tester.scrollUntilVisible(
      finder,
      150,
      scrollable: find
          .descendant(
            of: find.byKey(const ValueKey('vendor-counter-scroll')),
            matching: find.byType(Scrollable),
          )
          .first,
    );
  }
  await tester.ensureVisible(finder.first);
  await tester.pumpAndSettle();
  await tester.tap(finder.first);
  await tester.pumpAndSettle();
}

void main() {
  setUp(() => SharedPreferences.setMockInitialValues({}));
  for (final width in [390.0, 820.0, 1280.0]) {
    testWidgets(
      'vendor typed review handover and private clearing at ${width.toInt()}px',
      (tester) async {
        tester.view.physicalSize = Size(width, 1200);
        tester.view.devicePixelRatio = 1;
        addTearDown(tester.view.resetPhysicalSize);
        addTearDown(tester.view.resetDevicePixelRatio);
        vendorLabels(tester);
        final fake = VendorFixture(),
            repo = VendorRepository(
              mode: AuthMode.bearer,
              createDio: () => Dio()..httpClientAdapter = fake.api,
            );
        addTearDown(repo.dispose);
        await tester.pumpWidget(VendorApp(repository: repo));
        await tester.pumpAndSettle();
        await login(tester);
        expect(repo.profile!.userId, 71);
        final field = find.byWidgetPredicate(
          (w) =>
              w is TextField &&
              w.decoration?.labelText == 'Order number or wallet order QR',
        );
        await tester.enterText(field, 'VO-000001');
        await tap(tester, 'Review order before handover');
        expect(
          find.text('Synthetic sandwich · 1 each · ZAR 12.50'),
          findsOneWidget,
        );
        expect(fake.writes, 0);
        await tap(
          tester,
          'I reviewed these exact goods and will hand them over once after successful validation.',
        );
        await tap(tester, 'Validate one handover');
        expect(fake.writes, 1);
        expect(find.text('Collected'), findsWidgets);
        expect(find.text('Validate one handover'), findsNothing);
        expect(await repo.collect(), isFalse);
        expect(fake.writes, 1);
        fake.allowed = false;
        await tester.runAsync(repo.refresh);
        await tester.pumpAndSettle();
        expect(
          find.text('Synthetic sandwich · 1 each · ZAR 12.50'),
          findsNothing,
        );
        expect(find.text('VO-000001'), findsNothing);
        expect(find.text('Vendor sign-in'), findsOneWidget);
        expect(tester.takeException(), isNull);
      },
    );
  }
  testWidgets(
    'camera QR callback previews goods and requires a separate explicit confirmation',
    (tester) async {
      vendorLabels(tester);
      final fake = VendorFixture();
      final repo = VendorRepository(
        mode: AuthMode.bearer,
        createDio: () => Dio()..httpClientAdapter = fake.api,
      );
      addTearDown(repo.dispose);
      var scans = 0;
      await tester.pumpWidget(
        VendorApp(
          repository: repo,
          scanQr: (_) async {
            scans++;
            return 'navigateed:order:${'b' * 64}';
          },
        ),
      );
      await tester.pumpAndSettle();
      await login(tester);
      await tap(tester, 'Scan order QR with camera');
      expect(scans, 1);
      expect(repo.receipt!.number, 'VO-000001');
      expect(fake.writes, 0);
      await tap(
        tester,
        'I reviewed these exact goods and will hand them over once after successful validation.',
      );
      await tap(tester, 'Validate one handover');
      expect(fake.writes, 1);
      expect(tester.takeException(), isNull);
    },
  );
  for (final width in [390.0, 820.0, 1280.0]) {
    testWidgets(
      'vendor settlement review has exact original totals at ${width.toInt()}px',
      (tester) async {
        await tester.binding.setSurfaceSize(Size(width, 1000));
        addTearDown(() => tester.binding.setSurfaceSize(null));
        vendorLabels(tester);
        final fixture = VendorFixture();
        final repo = VendorRepository(
          mode: AuthMode.bearer,
          createDio: () => Dio()..httpClientAdapter = fixture.api,
        );
        addTearDown(repo.dispose);
        await tester.pumpWidget(VendorApp(repository: repo));
        await tester.pumpAndSettle();
        await login(tester);
        await tap(tester, 'View this counter settlement history');
        await tap(tester, 'Settlement 501 · 2026-10-28–2026-10-28');
        expect(
          find.text('Vendor settlement amount: ZAR 12.50'),
          findsOneWidget,
        );
        expect(find.text('Collector fee: ZAR 0.00'), findsOneWidget);
        expect(find.text('VAT-exclusive sales net: ZAR 12.50'), findsOneWidget);
        expect(fixture.writes, 0);
        fixture.allowed = false;
        await tester.runAsync(repo.refresh);
        await tester.pumpAndSettle();
        expect(find.text('Vendor sign-in'), findsOneWidget);
        expect(find.text('Settlement 501'), findsNothing);
        expect(tester.takeException(), isNull);
      },
    );
  }
}
