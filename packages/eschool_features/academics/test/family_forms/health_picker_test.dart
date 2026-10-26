import 'dart:async';
import 'dart:typed_data';
import 'package:eschool_academics/eschool_academics.dart';
import 'package:eschool_people/eschool_people.dart';
import 'package:flutter/material.dart';
import 'package:flutter_test/flutter_test.dart';
import 'fixture.dart';

void main() {
  testWidgets(
    'native document handoff clears the old body and reauthorizes after resume',
    (tester) async {
      final repo = Forms()..includeFile = true;
      final chooser = Completer<ProfileUpload?>();
      var choosing = false;
      await mount(
        tester,
        FamilyFormsScreen(
          repository: repo,
          pickHealthFile: () {
            choosing = true;
            expect(find.text('Synthetic private allergy'), findsNothing);
            return chooser.future;
          },
        ),
      );
      await tap(tester, 'Health forms');
      await tap(tester, 'Upload file');
      expect(choosing, isTrue);
      expect(find.text('Synthetic private allergy'), findsNothing);
      tester.binding.handleAppLifecycleStateChanged(AppLifecycleState.inactive);
      await tester.pump();
      tester.binding.handleAppLifecycleStateChanged(AppLifecycleState.resumed);
      await tester.pump();
      final bytes = Uint8List.fromList([37, 80, 68, 70]);
      chooser.complete(ProfileUpload('synthetic.pdf', bytes));
      await tester.pumpAndSettle();
      expect(repo.uploads.single, (11, 12, 'health_document', 'synthetic.pdf'));
      expect(bytes, [0, 0, 0, 0]);
      expect(find.text('Synthetic private allergy'), findsOneWidget);
      expect(tester.takeException(), isNull);
    },
  );
  testWidgets(
    'revoked document picker cannot upload or restore a medical body',
    (tester) async {
      final repo = Forms()..includeFile = true;
      final chooser = Completer<ProfileUpload?>();
      await mount(
        tester,
        FamilyFormsScreen(
          repository: repo,
          pickHealthFile: () => chooser.future,
        ),
      );
      await tap(tester, 'Health forms');
      await tap(tester, 'Upload file');
      repo.revoked = true;
      final bytes = Uint8List.fromList([1, 2, 3]);
      chooser.complete(ProfileUpload('synthetic.pdf', bytes));
      await tester.pumpAndSettle();
      expect(repo.uploads, isEmpty);
      expect(bytes, [0, 0, 0]);
      expect(find.text('Synthetic private allergy'), findsNothing);
    },
  );
  testWidgets(
    'expired picker is discarded and requires a new explicit choice',
    (tester) async {
      final repo = Forms()..includeFile = true;
      final chooser = Completer<ProfileUpload?>();
      var clock = Duration.zero;
      await mount(
        tester,
        FamilyFormsScreen(
          repository: repo,
          pickHealthFile: () => chooser.future,
          monotonicNow: () => clock,
        ),
      );
      await tap(tester, 'Health forms');
      await tap(tester, 'Upload file');
      clock = const Duration(seconds: 61);
      final bytes = Uint8List.fromList([1, 2, 3]);
      chooser.complete(ProfileUpload('synthetic.pdf', bytes));
      await tester.pumpAndSettle();
      expect(repo.uploads, isEmpty);
      expect(bytes, [0, 0, 0]);
      expect(find.text('Synthetic private allergy'), findsNothing);
      expect(
        find.text('The document selection expired. Choose it again.'),
        findsOneWidget,
      );
    },
  );
  testWidgets(
    'unsaved health edits must be saved before leaving for a picker',
    (tester) async {
      final repo = Forms()..includeFile = true;
      var choices = 0;
      await mount(
        tester,
        FamilyFormsScreen(
          repository: repo,
          pickHealthFile: () async {
            choices++;
            return null;
          },
        ),
      );
      await tap(tester, 'Health forms');
      await tester.enterText(
        find.byKey(const ValueKey('profile-field-allergies')),
        'Synthetic unsaved change',
      );
      await tester.pump();
      final button = tester.widget<OutlinedButton>(
        find.widgetWithText(OutlinedButton, 'Upload file'),
      );
      expect(button.onPressed, isNull);
      expect(choices, 0);
      expect(
        find.text('Save your changes before adding a document.'),
        findsOneWidget,
      );
    },
  );
  testWidgets(
    'private image route uses a scoped codec and vanishes with the health reader',
    (tester) async {
      final repo = Forms()..includeFile = true;
      await mount(tester, FamilyFormsScreen(repository: repo));
      await tap(tester, 'Health forms');
      final cached = PaintingBinding.instance.imageCache.currentSize;
      await tester.ensureVisible(find.text('View file'));
      await tester.tap(find.text('View file'));
      await tester.pump();
      await tester.runAsync(() async {
        await Future<void>.delayed(const Duration(milliseconds: 30));
      });
      await tester.pumpAndSettle();
      expect(find.byType(RawImage), findsOneWidget);
      final image = tester.widget<RawImage>(find.byType(RawImage)).image!;
      expect(PaintingBinding.instance.imageCache.currentSize, cached);
      tester.binding.handleAppLifecycleStateChanged(AppLifecycleState.hidden);
      expect(image.debugDisposed, isTrue);
      tester.binding.scheduleForcedFrame();
      await tester.pump();
      expect(find.byType(RawImage), findsNothing);
      expect(find.text('Synthetic private allergy'), findsNothing);
      tester.binding.handleAppLifecycleStateChanged(AppLifecycleState.resumed);
      await tester.pumpAndSettle();
      expect(find.byType(RawImage), findsNothing);
      expect(tester.takeException(), isNull);
    },
  );
}
