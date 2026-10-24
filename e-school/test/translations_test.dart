import 'dart:convert';
import 'dart:io';

import 'package:eschool/utils/appLanguages.dart';
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
  test('Afrikaans and isiZulu are offered and preserve placeholders', () {
    expect(offered, containsAll(['af', 'zu']));
    final english = labels(defaultLanguageCode);
    final tokens = RegExp(
      r'\{\{[^{}]*\}\}|\{[A-Za-z_][^{}]*\}|(?<!\w):[A-Za-z_]\w*|%(?:\d+\$)?[sdif]|<[^>]+>',
    );
    List<String> protected(String value) =>
        tokens.allMatches(value).map((m) => m[0]!).toList()..sort();
    for (final code in ['af', 'zu']) {
      final translated = labels(code);
      for (final entry in english.entries) {
        final value = translated[entry.key];
        expect(value, isA<String>(), reason: '$code ${entry.key}');
        if ((entry.value as String).trim().isNotEmpty)
          expect(
            (value as String).trim(),
            isNotEmpty,
            reason: '$code ${entry.key}',
          );
        expect(
          protected(value as String),
          protected(entry.value as String),
          reason: '$code ${entry.key}',
        );
      }
    }
  });

  test('school terminology matches the working school glossary', () {
    final glossary = jsonDecode(
      File('../engineering/languages/glossary.json').readAsStringSync(),
    ) as Map<String, dynamic>;
    final english = labels(defaultLanguageCode);
    for (final code in ['af', 'zu']) {
      final translated = labels(code);
      for (final entry in english.entries) {
        final terms = glossary[entry.value];
        if (terms != null)
          expect(
            translated[entry.key],
            terms[code],
            reason: '$code ${entry.key}',
          );
      }
    }
  });
}
