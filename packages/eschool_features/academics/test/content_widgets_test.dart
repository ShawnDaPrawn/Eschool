import 'dart:async';
import 'package:eschool_academics/eschool_academics.dart';
import 'package:eschool_academics/testing.dart';
import 'package:eschool_core/eschool_core.dart';
import 'package:flutter/material.dart';
import 'package:flutter_test/flutter_test.dart';
import 'content_cubit_test.dart' show FakeContentRepository;

Future<void> frame(WidgetTester tester, Widget child, double width) async {
  tester.view.physicalSize = Size(width, 900);
  tester.view.devicePixelRatio = 1;
  addTearDown(tester.view.resetPhysicalSize);
  addTearDown(tester.view.resetDevicePixelRatio);
  await tester.pumpWidget(
    MaterialApp(theme: ThemeData(useMaterial3: true), home: child),
  );
  await tester.pumpAndSettle();
}

Future<void> tap(WidgetTester tester, Finder finder) async {
  await tester.ensureVisible(finder);
  await tester.tap(finder);
  await tester.pumpAndSettle();
}

void main() {
  for (final width in [390.0, 1024.0, 1280.0]) {
    testWidgets('family opens readable current material at $width', (
      tester,
    ) async {
      final repo = FakeContentRepository();
      await frame(
        tester,
        LearningContentScreen(
          repository: repo,
          learner: 11,
          learnerName: 'Thandi',
        ),
        width,
      );
      await tap(tester, find.text('Fractions'));
      expect(find.byType(LearningContentReaderScreen), findsOneWidget);
      expect(find.text('One half equals two quarters.'), findsOneWidget);
      expect(repo.reads, 1);
      expect(tester.takeException(), isNull);
    });
    testWidgets('teacher hub and draft editor at $width', (tester) async {
      final repo = FakeContentRepository();
      await frame(tester, ContentHubScreen(repository: repo), width);
      await tap(tester, find.text('Fractions'));
      expect(find.byType(ContentEditorScreen), findsOneWidget);
      final field = find.byWidgetPredicate(
        (w) => w is TextField && w.decoration?.labelText == 'Markdown draft',
      );
      await tester.ensureVisible(field);
      await tester.enterText(field, '# My reviewed lesson');
      await tester.pump();
      await tap(tester, find.text('Save reviewed draft'));
      expect(repo.drafts.single.markdown, '# My reviewed lesson');
      await tap(tester, find.text('Preview publication'));
      expect(repo.publishes, 0);
      await tap(
        tester,
        find.text(
          'I reviewed the text, topic tags and published teaching groups.',
        ),
      );
      await tap(tester, find.text('Publish reviewed content'));
      expect(repo.publishes, 1);
      expect(tester.takeException(), isNull);
    });
  }
  testWidgets(
    'dirty draft cannot withdraw or preview and refresh requires comparison',
    (tester) async {
      final repo = FakeContentRepository();
      await frame(
        tester,
        ContentEditorScreen(
          repository: repo,
          configuration: ContentConfiguration.fromJson(
            contentConfigurationFixture(),
          ),
          itemId: 10,
        ),
        1024,
      );
      final field = find.byWidgetPredicate(
        (w) => w is TextField && w.decoration?.labelText == 'Markdown draft',
      );
      await tester.ensureVisible(field);
      await tester.enterText(field, '# Unsaved private revision');
      await tester.pumpAndSettle();
      final withdrawal = tester.widget<OutlinedButton>(
        find.widgetWithText(OutlinedButton, 'Withdraw published material'),
      );
      expect(withdrawal.onPressed, isNull);
      expect(
        tester
            .widget<OutlinedButton>(
              find.widgetWithText(OutlinedButton, 'Preview publication'),
            )
            .onPressed,
        isNull,
      );
      repo.revision = 3;
      await tap(tester, find.byTooltip('Refresh content status'));
      expect(
        find.text(
          'Content changed. Your submitted draft is kept for comparison.',
        ),
        findsOneWidget,
      );
      expect(
        tester.widget<TextField>(field).controller!.text,
        '# Unsaved private revision',
      );
      expect(
        tester
            .widget<FilledButton>(
              find.widgetWithText(FilledButton, 'Save reviewed draft'),
            )
            .onPressed,
        isNull,
      );
      await tap(tester, find.text('Keep my draft and use current revisions'));
      await tap(tester, find.text('Save reviewed draft'));
      expect(repo.drafts.single.itemRevision, 3);
      expect(repo.drafts.single.markdown, '# Unsaved private revision');
    },
  );

  testWidgets(
    'reader clears on pause and access loss instead of retaining old body or title',
    (tester) async {
      final repo = FakeContentRepository();
      await frame(
        tester,
        LearningContentReaderScreen(
          repository: repo,
          learner: 11,
          version: 11,
          title: 'Private old title',
        ),
        390,
      );
      expect(find.text('One half equals two quarters.'), findsOneWidget);
      tester.binding.handleAppLifecycleStateChanged(AppLifecycleState.inactive);
      await tester.pumpAndSettle();
      expect(find.text('One half equals two quarters.'), findsNothing);
      expect(find.text('Private old title'), findsNothing);
      repo.error = ApiException('Forbidden', statusCode: 403);
      tester.binding.handleAppLifecycleStateChanged(AppLifecycleState.resumed);
      await tester.pumpAndSettle();
      expect(find.text('One half equals two quarters.'), findsNothing);
      expect(tester.takeException(), isNull);
    },
  );
  testWidgets(
    'view-only material exposes no active save or publication action',
    (tester) async {
      final repo = FakeContentRepository()..writable = false;
      await frame(
        tester,
        ContentEditorScreen(
          repository: repo,
          configuration: ContentConfiguration.fromJson(
            contentConfigurationFixture(),
          ),
          itemId: 10,
        ),
        1024,
      );
      final preview = tester.widget<OutlinedButton>(
        find.widgetWithText(OutlinedButton, 'Preview publication'),
      );
      expect(preview.onPressed, isNull);
      expect(
        find.text('This material is view only with your current allocation.'),
        findsOneWidget,
      );
    },
  );
  testWidgets('late reader response after widget disposal stays private', (
    tester,
  ) async {
    final repo = FakeContentRepository(), pending = Completer<ContentReader>();
    repo.fetchReader = () => pending.future;
    tester.view.physicalSize = const Size(390, 900);
    tester.view.devicePixelRatio = 1;
    addTearDown(tester.view.resetPhysicalSize);
    addTearDown(tester.view.resetDevicePixelRatio);
    await tester.pumpWidget(
      MaterialApp(
        home: LearningContentReaderScreen(
          repository: repo,
          learner: 11,
          version: 11,
          title: 'Learning content',
        ),
      ),
    );
    await tester.pump();
    await tester.pumpWidget(const MaterialApp(home: Text('Signed out')));
    pending.complete(ContentReader.fromJson(contentReaderFixture()));
    await tester.pumpAndSettle();
    expect(find.text('Signed out'), findsOneWidget);
    expect(repo.reads, 0);
    expect(tester.takeException(), isNull);
  });
  testWidgets('Markdown never creates network images from source text', (
    tester,
  ) async {
    await frame(
      tester,
      const Scaffold(
        body: ContentMarkdownView(
          markdown:
              '# Lesson\n\n![tracking](https://other.example/pixel.png)\n\n<script>alert(1)</script>',
        ),
      ),
      390,
    );
    expect(find.byType(Image), findsNothing);
    expect(find.text('Lesson'), findsOneWidget);
    expect(tester.takeException(), isNull);
  });
}
