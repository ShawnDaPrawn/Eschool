import 'dart:io';

import 'package:flutter_test/flutter_test.dart';

// Colours come from the theme (Theme.of(context).colorScheme and
// StatusColors.of(context)), so the brand changes in one place. The theme
// itself lives in lib/src/theme/ and is the only place allowed to name
// colours. See wiki/UI-Standards.md.
final rawColor = RegExp(
  r'\bColor\(0x|\bColor\.from(ARGB|RGBO)\(|\bColors\.(?!transparent\b)[a-z]',
);
const allowedDir = 'lib/src/theme/';

void main() {
  test('no colours are hard-coded outside the theme', () {
    final offenders = <String>[];
    final files = Directory('lib')
        .listSync(recursive: true)
        .whereType<File>()
        .where((file) => file.path.endsWith('.dart'))
        .where((file) => !file.path.startsWith(allowedDir));
    for (final file in files) {
      final lines = file.readAsLinesSync();
      for (var i = 0; i < lines.length; i++) {
        final line = lines[i].trim();
        if (!line.startsWith('//') && rawColor.hasMatch(line)) {
          offenders.add('${file.path}:${i + 1}: $line');
        }
      }
    }
    expect(
      offenders,
      isEmpty,
      reason: 'Use a colour scheme role or StatusColors instead.',
    );
  });
}
