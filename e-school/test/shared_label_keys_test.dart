import 'dart:convert';
import 'dart:io';

import 'package:eschool/utils/appLanguages.dart';
import 'package:eschool_core/eschool_core.dart';
import 'package:flutter_test/flutter_test.dart';

// The widgets shared with the other app (eschool_core) read their labels from
// this app's language files. translations_test checks that every language has
// the English keys, so checking English is enough.
void main() {
  test('every label the shared widgets use is in the English file', () {
    final labels =
        jsonDecode(
              File(
                'assets/languages/$defaultLanguageCode.json',
              ).readAsStringSync(),
            )
            as Map<String, dynamic>;

    expect(sharedLabelKeys.where((key) => !labels.containsKey(key)), isEmpty);
  });
}
