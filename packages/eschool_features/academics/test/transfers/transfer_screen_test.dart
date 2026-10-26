import 'dart:async';
import 'dart:typed_data';
import 'package:eschool_academics/eschool_academics.dart';
import 'package:eschool_academics/testing.dart';
import 'package:flutter/material.dart';
import 'package:flutter_test/flutter_test.dart';
import 'fake_transfer.dart';

Future<void> show(WidgetTester tester, FakeTransfer repo) async {
  await tester.pumpWidget(
    MaterialApp(home: TransferConsentScreen(repository: repo)),
  );
  await tester.pumpAndSettle();
  await tester.tap(find.text('Synthetic transfer learner'));
  await tester.pumpAndSettle();
}

Future<void> tap(WidgetTester tester, Finder finder) async {
  await tester.ensureVisible(finder);
  await tester.pumpAndSettle();
  await tester.tap(finder);
  await tester.pumpAndSettle();
}

final consent = find.widgetWithText(FilledButton, 'Consent to this transfer');
final approval = find.widgetWithText(
  CheckboxListTile,
  'I reviewed these records, the named receiving school and dates, and consent to this exact package.',
);
void main() {
  testWidgets(
    'optional gallery retention is off by default and exact retry preserves its own fingerprint',
    (tester) async {
      final repo = FakeTransfer()
        ..photoChoice = true
        ..failWrite = true;
      await show(tester, repo);
      final keep = find.widgetWithText(
        CheckboxListTile,
        'Keep approved photo tags for this learner',
      );
      expect(tester.widget<CheckboxListTile>(keep).value, isFalse);
      await tap(tester, keep);
      await tap(tester, approval);
      await tap(tester, consent);
      expect(repo.writes.single, contains('"keep_photo_tags":true'));
      expect(
        repo.writes.single,
        contains('synthetic-photo-consent-fingerprint'),
      );
      await tap(
        tester,
        find.widgetWithText(FilledButton, 'Retry the same transfer action'),
      );
      expect(repo.writes.first, repo.writes.last);
    },
  );
  for (final width in [390.0, 820.0, 1280.0]) {
    testWidgets(
      'guardian reviews exact destination and unknown history at $width',
      (tester) async {
        tester.view.physicalSize = Size(width, 1000);
        tester.view.devicePixelRatio = 1;
        addTearDown(tester.view.resetPhysicalSize);
        addTearDown(tester.view.resetDevicePixelRatio);
        final repo = FakeTransfer();
        await show(tester, repo);
        expect(find.text('Receiving demo school'), findsOneWidget);
        expect(find.text('Synthetic shared document.pdf'), findsOneWidget);
        expect(find.text('0.00%'), findsNothing);
        expect(tester.widget<FilledButton>(consent).onPressed, isNull);
        await tap(tester, approval);
        await tap(tester, consent);
        expect(repo.writes, hasLength(1));
        expect(repo.writes.single, contains('"fingerprint":"${'a' * 64}"'));
        expect(repo.writes.single, contains('"version":"school-transfer-v1"'));
        expect(find.text('Consent to this transfer'), findsNothing);
        expect(tester.takeException(), isNull);
      },
    );
  }
  testWidgets(
    'an unknown result retries the immutable UUID and exact package',
    (tester) async {
      final repo = FakeTransfer()..failWrite = true;
      await show(tester, repo);
      await tap(tester, approval);
      await tap(tester, consent);
      expect(repo.writes, hasLength(1));
      expect(tester.widget<FilledButton>(consent).onPressed, isNull);
      await tap(
        tester,
        find.widgetWithText(FilledButton, 'Retry the same transfer action'),
      );
      expect(repo.writes, hasLength(2));
      expect(repo.writes[0], repo.writes[1]);
      expect(find.text('Retry the same transfer action'), findsNothing);
    },
  );
  testWidgets(
    'a known conflict reloads revision and clears approval before a fresh action',
    (tester) async {
      final repo = FakeTransfer()..conflict = true;
      await show(tester, repo);
      await tap(tester, approval);
      await tap(tester, consent);
      expect(repo.revision, 2);
      expect(tester.widget<CheckboxListTile>(approval).value, isFalse);
      expect(tester.widget<FilledButton>(consent).onPressed, isNull);
      expect(
        find.text(
          'The transfer changed. Review its current records before trying again.',
        ),
        findsOneWidget,
      );
      await tap(tester, approval);
      await tap(tester, consent);
      expect(repo.writes.last, contains('"revision":2'));
      expect(repo.writes.first, isNot(repo.writes.last));
    },
  );
  testWidgets('a resumed denied reader erases all frozen personal records', (
    tester,
  ) async {
    final repo = FakeTransfer();
    await show(tester, repo);
    tester.binding.handleAppLifecycleStateChanged(AppLifecycleState.inactive);
    await tester.pump();
    expect(find.text('Synthetic transfer learner'), findsNothing);
    expect(find.text('Synthetic shared document.pdf'), findsNothing);
    repo.deny = true;
    tester.binding.handleAppLifecycleStateChanged(AppLifecycleState.resumed);
    await tester.pumpAndSettle();
    expect(find.text('Receiving demo school'), findsNothing);
    expect(
      find.text(
        'Transfers are unavailable. Check your guardian access and retry.',
      ),
      findsOneWidget,
    );
  });
  testWidgets('a late private body cannot repopulate a suspended reader', (
    tester,
  ) async {
    final repo = FakeTransfer()..delayed = Completer<TransferDetail>();
    await tester.pumpWidget(
      MaterialApp(home: TransferConsentScreen(repository: repo)),
    );
    await tester.pumpAndSettle();
    await tester.tap(find.text('Synthetic transfer learner'));
    await tester.pump();
    tester.binding.handleAppLifecycleStateChanged(AppLifecycleState.inactive);
    await tester.pump();
    repo.delayed!.complete(TransferDetail.fromJson(transferDetailFixture()));
    await tester.pump();
    expect(find.text('Receiving demo school'), findsNothing);
    expect(find.text('Synthetic shared document.pdf'), findsNothing);
    tester.binding.handleAppLifecycleStateChanged(AppLifecycleState.resumed);
    await tester.pumpAndSettle();
  });
  testWidgets(
    'read-only access and withdrawals use their explicit capabilities',
    (tester) async {
      final repo = FakeTransfer()..writable = false;
      await show(tester, repo);
      expect(find.text('Consent to this transfer'), findsNothing);
      expect(repo.writes, isEmpty);
      repo.writable = true;
      repo.state = 'consented';
      await tap(tester, find.byTooltip('Refresh transfers'));
      final withdraw = find.widgetWithText(
        OutlinedButton,
        'Withdraw transfer consent',
      );
      expect(tester.widget<OutlinedButton>(withdraw).onPressed, isNull);
      await tap(
        tester,
        find.widgetWithText(
          CheckboxListTile,
          'I want to withdraw this pending transfer.',
        ),
      );
      await tap(tester, withdraw);
      expect(repo.writes.single, startsWith('withdraw:'));
      expect(repo.state, 'cancelled');
    },
  );
  testWidgets(
    'a denied export erases private records and writes no device file',
    (tester) async {
      final repo = FakeTransfer()..denyArchive = true;
      var saves = 0;
      await tester.pumpWidget(
        MaterialApp(
          home: TransferConsentScreen(
            repository: repo,
            saveArchive: (_) async {
              saves++;
            },
          ),
        ),
      );
      await tester.pumpAndSettle();
      await tester.tap(find.text('Synthetic transfer learner'));
      await tester.pumpAndSettle();
      await tap(
        tester,
        find.widgetWithText(OutlinedButton, 'Download signed transfer archive'),
      );
      expect(saves, 0);
      expect(find.text('Receiving demo school'), findsNothing);
      expect(find.text('Synthetic shared document.pdf'), findsNothing);
    },
  );
  testWidgets(
    'a late archive after suspension cannot open a device save dialog',
    (tester) async {
      final repo = FakeTransfer()..delayedArchive = Completer<Uint8List>();
      var saves = 0;
      await tester.pumpWidget(
        MaterialApp(
          home: TransferConsentScreen(
            repository: repo,
            saveArchive: (_) async {
              saves++;
            },
          ),
        ),
      );
      await tester.pumpAndSettle();
      await tester.tap(find.text('Synthetic transfer learner'));
      await tester.pumpAndSettle();
      final button = find.widgetWithText(
        OutlinedButton,
        'Download signed transfer archive',
      );
      await tester.ensureVisible(button);
      await tester.pumpAndSettle();
      await tester.tap(button);
      await tester.pump();
      tester.binding.handleAppLifecycleStateChanged(AppLifecycleState.inactive);
      await tester.pump();
      repo.delayedArchive!.complete(Uint8List.fromList([0x50, 0x4b, 3, 4]));
      await tester.pump();
      expect(saves, 0);
      expect(find.text('Synthetic shared document.pdf'), findsNothing);
      repo.deny = true;
      tester.binding.handleAppLifecycleStateChanged(AppLifecycleState.resumed);
      await tester.pumpAndSettle();
    },
  );
}
