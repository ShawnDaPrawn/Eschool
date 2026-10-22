import 'dart:convert';

import 'package:eschool_core/eschool_core.dart';
import 'package:flutter/material.dart';
import 'package:flutter/services.dart';
import 'package:flutter_test/flutter_test.dart';

/// The labels the screens use, served as the app's English file.
const Map<String, String> testLabels = {
  'notificationSettings': 'Notification settings',
  'notificationSettingsHint': 'Choose how your school reaches you.',
  'pushChannel': 'Push',
  'emailChannel': 'Email',
  'inAppChannel': 'In the app',
  'lockedByYourSchool': 'Your school always sends this.',
  'nothingToSetUpYet': 'Nothing to set up yet.',
  'notAvailableYet': 'This is not available yet.',
  'retry': 'Try again',
  'defaultErrorMessage': 'Something went wrong',
};

const Size phone = Size(390, 844);
const Size desktop = Size(1280, 800);

/// Pumps [child] as the apps run shared screens: the theme, the labels, an
/// [AppShell] and a column on wide windows, in a window of [size].
Future<void> pumpScreen(
  WidgetTester tester,
  Widget child, {
  Size size = phone,
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
      home: ContentColumn(child: child),
    ),
  );
  await tester.pumpAndSettle();
}
