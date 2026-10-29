import 'package:dio/dio.dart';
import 'package:eschool_session/eschool_session.dart';
import 'package:eschool_teacher/app/app.dart';
import 'package:eschool_teacher/app/staffBuildIdentity.dart';
import 'package:eschool_teacher/vendor/vendor_repository.dart';
import 'package:flutter/material.dart';
import 'package:flutter_test/flutter_test.dart';
import 'package:integration_test/integration_test.dart';

import 'support/smoke.dart' show pumpUntilFound, tapWhenReady;
import 'support/vendor_payment_fixture.dart';

import 'package:eschool_teacher/vendor/vendor_nfc.dart';

void main() {
  IntegrationTestWidgetsFlutterBinding.ensureInitialized();
  for (final mode in ['typed', 'qr']) {
    testWidgets(
      'dedicated vendor $mode review, one handover and revoked-access clearing',
      (tester) async {
        tester.testTextInput.register();
        expect(StaffBuildIdentity.current.canStartVendor, isTrue);
        var admissionScan = false, paymentScan = false;
        final fixture = VendorPaymentFixture();
        final repo = VendorRepository(
          mode: AuthMode.bearer,
          createDio: () =>
              Dio()..httpClientAdapter = VendorPaymentAdapter(fixture),
        );
        addTearDown(repo.dispose);
        await initializeApp(
          vendorRepository: repo,
          vendorScanQr: (_) async => paymentScan
              ? 'navigateed:wallet-pay:${'a' * 64}'
              : admissionScan
              ? 'navigateed:ticket:${'a' * 64}'
              : 'navigateed:order:${'b' * 64}',
          vendorReadCard: (_) async => const VendorSunTap(
            cardId: '00000000-0000-4000-8000-000000001904',
            picc: 'EF963FF7828658A599F3041510671E88',
            mac: '94EED9EE65337086',
          ),
        );
        Finder field(String label) => find.byWidgetPredicate(
          (w) => w is TextField && w.decoration?.labelText == label,
        );
        await pumpUntilFound(tester, find.text('Vendor sign-in'));
        await tester.enterText(field('School code'), 'jacaranda');
        await tester.enterText(field('Email or handle'), 'vendor@example.test');
        await tester.enterText(field('Password'), 'SyntheticVendor2026');
        await tapWhenReady(tester, find.text('Sign in to vendor counter'));
        await pumpUntilFound(tester, find.text('Synthetic tuckshop'));
        if (mode == 'typed') {
          await tester.enterText(
            field('Order number or wallet order QR'),
            'VO-000001',
          );
          await tapWhenReady(tester, find.text('Review order before handover'));
        } else {
          await tapWhenReady(tester, find.text('Scan order QR with camera'));
        }
        await pumpUntilFound(
          tester,
          find.text('Synthetic sandwich · 1 each · ZAR 12.50'),
        );
        expect(fixture.writes, 0);
        await tapWhenReady(
          tester,
          find.text(
            'I reviewed these exact goods and will hand them over once after successful validation.',
          ),
        );
        await tapWhenReady(tester, find.text('Validate one handover'));
        await pumpUntilFound(
          tester,
          find.text('Collection recorded. Hand over the listed goods once.'),
        );
        expect(fixture.writes, 1);
        expect(find.text('Validate one handover'), findsNothing);
        await tapWhenReady(
          tester,
          find.text('View this counter settlement history'),
        );
        await tapWhenReady(
          tester,
          find.text('Settlement 501 · 2026-10-28–2026-10-28'),
        );
        await pumpUntilFound(
          tester,
          find.text('Vendor settlement amount: ZAR 12.50'),
        );
        expect(find.text('Collector fee: ZAR 0.00'), findsOneWidget);
        await tester.scrollUntilVisible(
          find.text('Event admissions'),
          -250,
          scrollable: find
              .descendant(
                of: find.byKey(const ValueKey('vendor-counter-scroll')),
                matching: find.byType(Scrollable),
              )
              .first,
        );
        admissionScan = true;
        await tapWhenReady(tester, find.text('Event admissions'));
        if (mode == 'typed') {
          await pumpUntilFound(
            tester,
            field('Current ticket number or admission QR'),
          );
          await tester.enterText(
            field('Current ticket number or admission QR'),
            'TK-${'A' * 20}',
          );
          await tapWhenReady(
            tester,
            find.text('Review admission before entry'),
          );
        } else {
          await tapWhenReady(
            tester,
            find.text('Scan admission QR with camera'),
          );
        }
        await pumpUntilFound(tester, find.textContaining('Asha Synthetic'));
        await tapWhenReady(
          tester,
          find.text(
            'I verified this original admission and will admit it once.',
          ),
        );
        await tapWhenReady(tester, find.text('Confirm one actual admission'));
        await pumpUntilFound(
          tester,
          find.text('One admission recorded. Admit this ticket once.'),
        );
        expect(fixture.admissionWrites, 1);
        expect(fixture.writes, 1);
        Future<void> paymentFind(Finder finder) async {
          if (finder.evaluate().isEmpty) {
            await tester.scrollUntilVisible(
              finder,
              200,
              scrollable: find.byType(Scrollable).last,
            );
          }
          await pumpUntilFound(tester, finder);
          await Scrollable.ensureVisible(
            tester.element(finder.first),
            alignment: 0.5,
          );
          await tester.pump(const Duration(milliseconds: 300));
        }

        paymentScan = true;
        await tester.scrollUntilVisible(
          find.text('Wallet payments'),
          -250,
          scrollable: find.byType(Scrollable).first,
        );
        await tapWhenReady(tester, find.text('Wallet payments'));
        if (mode == 'typed') {
          await tapWhenReady(tester, find.text('Scan wallet payment QR'));
        } else {
          final reader = field('Private reader pairing credential');
          await paymentFind(reader);
          await tester.enterText(reader, VendorPaymentFixture.readerCredential);
          await tapWhenReady(tester, find.text('Pair payment reader'));
          final quantity = find.byType(DropdownButtonFormField<int>);
          await paymentFind(quantity);
          await tester.tap(quantity);
          await tester.pumpAndSettle();
          await tester.tap(find.text('1').last);
          await tester.pumpAndSettle();
          await tapWhenReady(
            tester,
            find.text('Tap school card for these goods'),
          );
          await pumpUntilFound(tester, find.text('Asha Synthetic'));
          expect(find.byType(Image), findsOneWidget);
        }
        await pumpUntilFound(tester, find.text('Review this counter payment'));
        expect(fixture.paymentWrites, 0);
        await tapWhenReady(
          tester,
          find.text(
            mode == 'typed'
                ? 'I checked the exact items and total and will hand over these goods once.'
                : 'I checked this current photo, the exact items and total, and will hand over these goods once.',
          ),
        );
        await tapWhenReady(
          tester,
          find.text('Confirm wallet payment and handover'),
        );
        await pumpUntilFound(
          tester,
          find.text('Payment recorded. Hand over the listed goods once.'),
        );
        expect(fixture.paymentWrites, 1);
        expect(fixture.writes, 1);
        expect(fixture.admissionWrites, 1);
        await tester.pageBack();
        await tester.pumpAndSettle();
        fixture.allowed = false;
        await tester.runAsync(repo.refresh);
        await tester.pumpAndSettle();
        await pumpUntilFound(tester, find.text('Vendor sign-in'));
        expect(find.text('VO-000001'), findsNothing);
        expect(find.textContaining('Synthetic sandwich'), findsNothing);
        expect(
          fixture.api.requests.every((r) => r.uri.path.contains('/v2/vendor/')),
          isTrue,
        );
        expect(
          fixture.api.requests
              .where((r) => !r.uri.path.endsWith('/sign-in'))
              .every(
                (r) =>
                    r.headers['X-School-Id'] == '1' &&
                    r.headers['X-Account-Id'] == '71',
              ),
          isTrue,
        );
        expect(tester.takeException(), isNull);
      },
    );
  }
}
