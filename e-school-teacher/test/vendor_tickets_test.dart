import 'package:dio/dio.dart';
import 'package:eschool_session/eschool_session.dart';
import 'package:eschool_teacher/vendor/vendor_app.dart';
import 'package:eschool_teacher/vendor/vendor_repository.dart';
import 'package:flutter/material.dart';
import 'package:flutter_test/flutter_test.dart';
import 'package:shared_preferences/shared_preferences.dart';

import '../integration_test/support/vendor_ticket_fixture.dart';
import 'vendor_widgets_test.dart' show vendorLabels, login, tap;

Map<String, dynamic> credentials() => {
  'school': 'jacaranda',
  'email': 'vendor@example.test',
  'password': 'synthetic',
};
VendorRepository repository(VendorTicketFixture fake) => VendorRepository(
  mode: AuthMode.bearer,
  createDio: () => Dio()..httpClientAdapter = fake.api,
);
void main() {
  TestWidgetsFlutterBinding.ensureInitialized();
  setUp(() => SharedPreferences.setMockInitialValues({}));
  test('scoped vendor admission records one original outcome and carries no buyer identity', () async {
    final fake = VendorTicketFixture(),
        repo = repository(VendorTicketFixture());
    repo.dispose();
    final actual = repository(fake);
    expect(await actual.signIn(credentials()), isTrue);
    expect(actual.profile!.ticketingAvailable, isTrue);
    expect(await actual.lookupAdmission('TK-${'A' * 20}'), isTrue);
    expect(actual.admission!.namedLearner, 'Asha Synthetic');
    expect(await actual.admit(), isTrue);
    expect(actual.admission!.status, 'redeemed');
    expect(fake.admissionWrites, 1);
    expect(fake.writes, 0);
    expect(actual.message, 'One admission recorded. Admit this ticket once.');
    expect(await actual.admit(), isFalse);
    expect(fake.admissionWrites, 1);
    actual.dispose();
  });
  test('lost admission response keeps only opaque UUID and recovers without admitting again', () async {
    final fake = VendorTicketFixture()..loseAdmissionReply = true;
    final repo = repository(fake);
    await repo.signIn(credentials());
    await repo.lookupAdmission('navigateed:ticket:${'a' * 64}');
    expect(await repo.admit(), isFalse);
    expect(repo.hasPendingAdmission, isTrue);
    expect(repo.admission, isNull);
    expect(fake.admissionWrites, 1);
    final prefs = await SharedPreferences.getInstance();
    final pending = prefs.getStringList('vendor-admission-pending:1:71')!;
    expect(pending.single, startsWith('81:'));
    expect(pending.join(), isNot(contains('ticket:')));
    expect(pending.join(), isNot(contains('Asha')));
    repo.dispose();
    final restored = repository(fake);
    await restored.signIn(credentials());
    expect(restored.hasPendingAdmission, isTrue);
    expect(await restored.recover(), isTrue);
    expect(restored.admission!.status, 'redeemed');
    expect(restored.message, contains('do not admit this ticket again'));
    expect(restored.hasPending, isFalse);
    expect(fake.admissionWrites, 1);
    restored.dispose();
  });
  test('revocation and background suspension clear original admission and scanner credential', () async {
    final fake = VendorTicketFixture();
    final repo = repository(fake);
    await repo.signIn(credentials());
    await repo.lookupAdmission('TK-${'A' * 20}');
    expect(repo.admission, isNotNull);
    repo.suspend();
    expect(repo.admission, isNull);
    await repo.resume();
    await repo.lookupAdmission('TK-${'A' * 20}');
    fake.allowed = false;
    expect(await repo.lookupAdmission('TK-${'A' * 20}'), isFalse);
    expect(repo.admission, isNull);
    expect(repo.signedIn, isFalse);
    repo.dispose();
  });
  testWidgets(
    'vendor scanner has an explicit admission mode and one-entry confirmation',
    (tester) async {
      vendorLabels(tester);
      final fake = VendorTicketFixture();
      final repo = repository(fake);
      await tester.pumpWidget(
        VendorApp(
          repository: repo,
          scanQr: (_) async => 'navigateed:ticket:${'a' * 64}',
        ),
      );
      await tester.pumpAndSettle();
      await login(tester);
      await tap(tester, 'Event admissions');
      final scanner = find.text('Scan admission QR with camera');
      await tester.ensureVisible(scanner);
      await tester.tap(scanner);
      await tester.pumpAndSettle();
      expect(find.text('Asha Synthetic'), findsNothing);
      expect(find.textContaining('Asha Synthetic'), findsOneWidget);
      await tap(
        tester,
        'I verified this original admission and will admit it once.',
      );
      await tap(tester, 'Confirm one actual admission');
      expect(fake.admissionWrites, 1);
      expect(find.text('Confirm one actual admission'), findsNothing);
      expect(tester.takeException(), isNull);
      await tester.pumpWidget(const SizedBox.shrink());
      repo.dispose();
    },
  );
}
