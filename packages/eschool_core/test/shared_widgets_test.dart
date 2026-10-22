import 'package:eschool_core/eschool_core.dart';
import 'package:flutter/material.dart';
import 'package:flutter_test/flutter_test.dart';

import 'support/shared_widget_harness.dart';

void main() {
  group('ErrorContainer', () {
    testWidgets('shows the message the app maps the code to', (tester) async {
      var retries = 0;
      await pumpShared(
        tester,
        ErrorContainer(
          errorMessageCode: '113',
          showErrorImage: false,
          animate: false,
          onTapRetry: () => retries++,
        ),
      );

      expect(find.text('That name is taken'), findsOneWidget);
      await tester.tap(find.text('Try again'));
      expect(retries, 1);
    });

    testWidgets('shows a message given as text as it is', (tester) async {
      await pumpShared(
        tester,
        const ErrorContainer(
          errorMessageText: 'The server said no',
          showErrorImage: false,
          showRetryButton: false,
          animate: false,
        ),
      );

      expect(find.text('The server said no'), findsOneWidget);
      expect(find.text('Try again'), findsNothing);
    });
  });

  testWidgets('LoadMoreErrorWidget asks to retry and retries on tap', (
    tester,
  ) async {
    var retries = 0;
    await pumpShared(tester, LoadMoreErrorWidget(onTapRetry: () => retries++));

    await tester.tap(find.text('Could not load more, tap to retry'));
    expect(retries, 1);
  });

  testWidgets('an attachment opens and draws its button through the app', (
    tester,
  ) async {
    final material = StudyMaterial.fromURL('https://school.test/notes.pdf');
    late RecordingShell shell;
    shell = await pumpShared(
      tester,
      LayoutBuilder(
        builder: (context, constraints) =>
            StudyMaterialWithDownloadButtonContainer(
              boxConstraints: constraints,
              studyMaterial: material,
            ),
      ),
    );

    expect(find.text('notes.pdf'), findsOneWidget);
    expect(find.byKey(const Key('app-button')), findsOneWidget);
    await tester.tap(find.text('notes.pdf'));
    expect(shell.opened, [material]);
  });

  testWidgets('lists leave room for the bottom bar the app sets', (
    tester,
  ) async {
    tester.view.physicalSize = const Size(400, 800);
    tester.view.devicePixelRatio = 1;
    addTearDown(tester.view.reset);
    late double padding;
    await pumpShared(
      tester,
      Builder(
        builder: (context) {
          padding = SharedUiUtils.getScrollViewBottomPadding(context);
          return const SizedBox();
        },
      ),
      bottomNavigationHeightPercentage: 0.075,
    );

    expect(padding, 800 * 0.075 + 25 * 1.5);
  });

  test('SharedUiUtils keeps the apps\' formats', () {
    expect(SharedUiUtils.formatDate(DateTime(2026, 3, 7)), '07-03-2026');
    expect(
      SharedUiUtils.getYoutubeThumbnailUrl('https://youtu.be/abc123'),
      'https://img.youtube.com/vi/abc123/hqdefault.jpg',
    );
    expect(
      SharedUiUtils.getYoutubeThumbnailUrl(
        'https://www.youtube.com/watch?v=xyz',
      ),
      'https://img.youtube.com/vi/xyz/hqdefault.jpg',
    );
    expect(SharedUiUtils.getYoutubeThumbnailUrl('https://vimeo.com/1'), isNull);
    expect(
      SharedUiUtils.getLocaleFromLanguageCode('pt-BR'),
      const Locale('pt', 'BR'),
    );
  });

  test('file name extensions', () {
    expect('photo.JPG'.isImage(), isTrue);
    expect('voice.aac'.isAudioFile(), isTrue);
    expect('notes.pdf'.isImage(), isFalse);
    expect('hello world'.toTitleCase(), 'Hello World');
  });

  test('leave types read and write the API values', () {
    for (final type in LeaveType.values) {
      expect(LeaveType.fromAPIValue(type.getAPIType()), type);
    }
    expect(LeaveType.fromAPIValue('anything else'), LeaveType.full);
    expect(LeaveType.secondHalf.getTypeKey(), secondHalfKey);
  });

  test('shared label keys are listed once each', () {
    expect(sharedLabelKeys.toSet().length, sharedLabelKeys.length);
    expect(sharedLabelKeys, contains(retryKey));
  });
}
