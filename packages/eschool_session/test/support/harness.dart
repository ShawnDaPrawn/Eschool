import 'dart:convert';

import 'package:eschool_core/eschool_core.dart';
import 'package:flutter/material.dart';
import 'package:flutter/services.dart';
import 'package:flutter_test/flutter_test.dart';

/// The labels the session widgets use, served as the app's English file.
const Map<String, String> testLabels = {
  'chooseChild': 'Choose a child',
  'switchChild': 'Switch child',
  'yourChildren': 'Your children',
  'switchSchool': 'Switch school',
  'chooseSchool': 'Choose a school',
  'retry': 'Try again',
  'defaultErrorMessage': 'Something went wrong',
};

/// The widths the apps lay out for: a phone, a tablet and a desktop.
const Size phone = Size(390, 844);
const Size tablet = Size(820, 1180);
const Size desktop = Size(1280, 800);

/// Pumps [child] the way the apps run shared widgets: the shared theme, the
/// app's labels and an [AppShell], in a window of [size].
Future<void> pumpApp(
  WidgetTester tester,
  Widget child, {
  Size size = phone,
  bool settle = true,
}) async {
  tester.view.physicalSize = size;
  tester.view.devicePixelRatio = 1;
  addTearDown(tester.view.reset);
  const String key = 'assets/languages/en.json';
  rootBundle.evict(key);
  final TestDefaultBinaryMessenger messenger =
      tester.binding.defaultBinaryMessenger;
  messenger.setMockMessageHandler('flutter/assets', (message) async {
    if (utf8.decode(message!.buffer.asUint8List()) == key) {
      return ByteData.sublistView(utf8.encode(jsonEncode(testLabels)));
    }
    return messenger.delegate.send('flutter/assets', message);
  });
  addTearDown(() => messenger.setMockMessageHandler('flutter/assets', null));
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
        errorMessageKeyFromCode: (_) => 'defaultErrorMessage',
        openStudyMaterial: (_, _) {},
        studyMaterialButton: (_, _) => const SizedBox(),
        bottomNavigationHeightPercentage: 0.08,
        child: navigator!,
      ),
      home: child,
    ),
  );
  if (settle) {
    await tester.pumpAndSettle();
  }
}
