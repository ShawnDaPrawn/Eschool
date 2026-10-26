import 'dart:async';
import 'dart:typed_data';
import 'package:eschool_academics/eschool_academics.dart';
import 'package:eschool_core/eschool_core.dart';
import 'package:eschool_people/eschool_people.dart';
import 'package:flutter/material.dart';
import 'package:flutter_test/flutter_test.dart';
import 'fixture.dart';

void main() {
  for (final width in [390.0, 820.0, 1280.0]) {
    testWidgets('exact guardian decision and unknown-result retry at $width', (
      tester,
    ) async {
      final repo = Forms()..unknown = true;
      await mount(tester, FamilyFormsScreen(repository: repo), width: width);
      expect(repo.listings.single, (11, null));
      await tap(tester, 'Synthetic trip');
      expect(find.text(privateWording), findsOneWidget);
      await tester.tap(find.byType(DropdownButtonFormField<String>));
      await tester.pumpAndSettle();
      await tester.tap(find.text('Yes').last);
      await tester.pumpAndSettle();
      await tester.enterText(find.byType(TextField), 'Synthetic Guardian');
      await tester.ensureVisible(find.byType(CheckboxListTile));
      await tester.tap(find.byType(CheckboxListTile));
      await tester.pump();
      await tap(tester, 'Record my signed decision');
      expect(find.text('Retry the same change'), findsOneWidget);
      expect(find.byType(TextField), findsNothing);
      await tap(tester, 'Retry the same change');
      expect(repo.signs, hasLength(2));
      expect(repo.signs.first.operationId, repo.signs.last.operationId);
      expect(repo.receipts, hasLength(1));
      expect(find.text('Last recorded decision: Yes'), findsOneWidget);
      expect(tester.takeException(), isNull);
    });
  }
  testWidgets(
    'staff roster uses effective permission, with no family health selector',
    (tester) async {
      final repo = Forms();
      await mount(tester, FamilyFormsScreen(repository: repo, staffOnly: true));
      expect(repo.listings.single, (null, 3));
      expect(find.text('Health forms'), findsNothing);
      await tap(tester, 'Synthetic trip');
      expect(find.textContaining('Permission is not current'), findsOneWidget);
      expect(
        find.textContaining('Last recorded decision: Yes'),
        findsOneWidget,
      );
      expect(find.byType(CheckboxListTile), findsNothing);
    },
  );
  testWidgets(
    'late consent body cannot return after background and resume requires a fresh source',
    (tester) async {
      final repo = Forms()..pendingRead = Completer();
      await mount(tester, FamilyFormsScreen(repository: repo));
      await tester.tap(find.text('Synthetic trip'));
      await tester.pump();
      tester.binding.handleAppLifecycleStateChanged(AppLifecycleState.hidden);
      await tester.pump();
      repo.pendingRead!.complete(FamilyConsentDetail.fromJson(detailJson()));
      await tester.pump();
      expect(find.text(privateWording), findsNothing);
      repo.revoked = true;
      tester.binding.handleAppLifecycleStateChanged(AppLifecycleState.resumed);
      await tester.pumpAndSettle();
      expect(find.text(privateWording), findsNothing);
      expect(find.text('Synthetic trip'), findsNothing);
    },
  );
  testWidgets(
    'nested health editor disappears on fresh denial and background',
    (tester) async {
      final repo = Forms();
      await mount(tester, FamilyFormsScreen(repository: repo));
      await tap(tester, 'Health forms');
      expect(find.text('Synthetic private allergy'), findsOneWidget);
      repo.revoked = true;
      await tester.tap(find.byIcon(Icons.refresh).last);
      await tester.pumpAndSettle();
      expect(find.text('Synthetic private allergy'), findsNothing);
      expect(
        find.text('Your family access changed. Reopen this screen.'),
        findsOneWidget,
      );
      tester.binding.handleAppLifecycleStateChanged(AppLifecycleState.hidden);
      await tester.pump();
      expect(find.text('Synthetic allergy'), findsNothing);
      tester.binding.handleAppLifecycleStateChanged(AppLifecycleState.resumed);
      await tester.pumpAndSettle();
      expect(find.text('Synthetic private allergy'), findsNothing);
    },
  );
  test(
    'late health file bytes are zeroized when the local health epoch ends',
    () async {
      final repo = Forms()..pendingFile = Completer();
      var active = true, denied = false;
      final adapter = HealthProfileAdapter(
        repo,
        FamilyFormChoice(id: 11, personId: 12, name: 'Synthetic child'),
        active: () => active,
        onDenied: () => denied = true,
      );
      const subject = ProfileSubject(
        personId: 12,
        schoolId: 1,
        profileType: 'learner',
      );
      final pending = adapter.readFile(
        subject,
        ProfileAttachment(
          id: fileToken,
          name: 'Synthetic',
          mime: 'image/png',
          size: 3,
          url: 'https://untrusted.example/file',
        ),
      );
      active = false;
      final bytes = Uint8List.fromList([1, 2, 3]);
      repo.pendingFile!.complete(bytes);
      await expectLater(pending, throwsA(isA<ApiException>()));
      expect(bytes, [0, 0, 0]);
      expect(denied, isTrue);
    },
  );
}
