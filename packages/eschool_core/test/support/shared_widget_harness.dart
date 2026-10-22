import 'dart:convert';

import 'package:eschool_core/eschool_core.dart';
import 'package:flutter/material.dart';
import 'package:flutter/services.dart';
import 'package:flutter_test/flutter_test.dart';

/// The label file every test serves as `assets/languages/en.json`.
const Map<String, String> testLabels = {
  'retry': 'Try again',
  'errorLoadingMoreTapToRetry': 'Could not load more, tap to retry',
  'defaultErrorMessage': 'Something went wrong',
  'nameAlreadyTaken': 'That name is taken',
  'noInternet': 'No internet connection',
  'viewAll': 'View all',
  'gallery': 'Gallery',
};

/// Serves [testLabels] where AppLocalization reads the app's English labels.
void serveTestLabels(WidgetTester tester) {
  const key = 'assets/languages/en.json';
  rootBundle.evict(key);
  tester.binding.defaultBinaryMessenger.setMockMessageHandler(
    'flutter/assets',
    (message) async {
      final String requested = utf8.decode(message!.buffer.asUint8List());
      if (requested == key) {
        return ByteData.sublistView(utf8.encode(jsonEncode(testLabels)));
      }
      return null;
    },
  );
  addTearDown(
    () => tester.binding.defaultBinaryMessenger.setMockMessageHandler(
      'flutter/assets',
      null,
    ),
  );
}

/// An [AppShell] that records what the shared widgets asked the app to do.
class RecordingShell {
  final List<StudyMaterial> opened = [];

  /// Maps codes the way the teacher app does for the one code tests use.
  String errorMessageKeyFromCode(String code) =>
      code == '113' ? 'nameAlreadyTaken' : 'defaultErrorMessage';

  void openStudyMaterial(BuildContext context, StudyMaterial studyMaterial) =>
      opened.add(studyMaterial);

  Widget studyMaterialButton(BuildContext context, StudyMaterial material) =>
      const Icon(Icons.download, key: Key('app-button'));
}

/// Pumps [child] the way the apps run shared widgets: the shared theme, the
/// app's labels and an [AppShell] above the navigator.
Future<RecordingShell> pumpShared(
  WidgetTester tester,
  Widget child, {
  double bottomNavigationHeightPercentage = 0.08,
}) async {
  serveTestLabels(tester);
  final shell = RecordingShell();
  await tester.pumpWidget(
    MaterialApp(
      theme: AppTheme.light(),
      locale: const Locale('en'),
      supportedLocales: const [Locale('en')],
      localizationsDelegates: const [
        AppLocalizationDelegate([
          AppLanguage(languageCode: 'en', languageName: 'English'),
        ]),
      ],
      builder: (context, navigator) => AppShell(
        errorMessageKeyFromCode: shell.errorMessageKeyFromCode,
        openStudyMaterial: shell.openStudyMaterial,
        studyMaterialButton: shell.studyMaterialButton,
        bottomNavigationHeightPercentage: bottomNavigationHeightPercentage,
        child: navigator!,
      ),
      home: Scaffold(body: child),
    ),
  );
  await tester.pumpAndSettle();
  return shell;
}
