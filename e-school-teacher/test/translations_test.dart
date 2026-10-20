import 'dart:convert';
import 'dart:io';

import 'package:eschool_teacher/utils/appLanguages.dart';
import 'package:flutter_test/flutter_test.dart';

// Run from the app folder (flutter test), so asset paths are relative to it.
const String languagesDir = 'assets/languages';

Map<String, dynamic> labels(String languageCode) =>
    jsonDecode(File('$languagesDir/$languageCode.json').readAsStringSync())
        as Map<String, dynamic>;

void main() {
  final offered = appLanguages.map((language) => language.languageCode).toSet();
  final files = Directory(languagesDir)
      .listSync()
      .map((file) => file.uri.pathSegments.last)
      .where((name) => name.endsWith('.json'))
      .map((name) => name.substring(0, name.length - '.json'.length))
      .toSet();

  test('every language the app offers has a translation file', () {
    expect(offered.difference(files), isEmpty);
  });

  test(
    'there are no translation files for languages the app does not offer',
    () {
      expect(files.difference(offered), isEmpty);
    },
  );

  test('every translation file has exactly the English keys', () {
    final englishKeys = labels(defaultLanguageCode).keys.toSet();
    for (final code in files) {
      final keys = labels(code).keys.toSet();
      expect(
        englishKeys.difference(keys),
        isEmpty,
        reason: '$code.json is missing keys',
      );
      expect(
        keys.difference(englishKeys),
        isEmpty,
        reason: '$code.json has keys English lacks',
      );
    }
  });
}
