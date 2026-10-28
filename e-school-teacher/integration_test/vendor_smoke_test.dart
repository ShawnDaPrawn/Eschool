import 'package:dio/dio.dart';
import 'package:eschool_session/eschool_session.dart';
import 'package:eschool_teacher/app/app.dart';
import 'package:eschool_teacher/app/staffBuildIdentity.dart';
import 'package:eschool_teacher/vendor/vendor_repository.dart';
import 'package:flutter/material.dart';
import 'package:flutter_test/flutter_test.dart';
import 'package:integration_test/integration_test.dart';

import 'support/smoke.dart' show pumpUntilFound, tapWhenReady;
import 'support/vendor_ticket_fixture.dart';

void main() {
  IntegrationTestWidgetsFlutterBinding.ensureInitialized();
  for (final mode in ['typed', 'qr']) {
    testWidgets(
      'dedicated vendor $mode review, one handover and revoked-access clearing',
      (tester) async {
        tester.testTextInput.register();
        expect(StaffBuildIdentity.current.canStartVendor, isTrue);
        var admissionScan = false;
        final fixture = VendorTicketFixture();
        final repo = VendorRepository(
          mode: AuthMode.bearer,
          createDio: () => Dio()..httpClientAdapter = fixture.api,
        );
        addTearDown(repo.dispose);
        await initializeApp(
          vendorRepository: repo,
          vendorScanQr: (_) async => admissionScan
              ? 'navigateed:ticket:${'a' * 64}'
              : 'navigateed:order:${'b' * 64}',
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
        admissionScan = true;
        await tapWhenReady(tester, find.text('Event admissions'));
        if (mode == 'typed') {
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
