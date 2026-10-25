import 'dart:convert';
import 'dart:io';

import 'package:flutter/services.dart';
import 'package:flutter_test/flutter_test.dart';

export '../integration_test/support/facilitator_fixture.dart';

void staffTestLabels(WidgetTester tester) {
  // Serve real labels used by this screen in a small asset. The full English
  // bundle uses an isolate for decoding; fake widget time cannot finish it.
  final english = jsonDecode(
    File('assets/languages/en.json').readAsStringSync(),
  ) as Map<String, dynamic>;
  final source = File('lib/facilitator/facilitator_app.dart')
      .readAsStringSync();
  final labels = {
    for (final key in english.keys)
      if (source.contains("'$key'") ||
          key.contains('group') ||
          key.contains('Group'))
        key: english[key],
  };
  final messenger = tester.binding.defaultBinaryMessenger;
  rootBundle.evict('assets/languages/en.json');
  messenger.setMockMessageHandler('flutter/assets', (message) async {
    if (utf8.decode(message!.buffer.asUint8List()) ==
        'assets/languages/en.json') {
      return ByteData.sublistView(utf8.encode(jsonEncode(labels)));
    }
    return messenger.delegate.send('flutter/assets', message);
  });
  addTearDown(() {
    messenger.setMockMessageHandler('flutter/assets', null);
    rootBundle.evict('assets/languages/en.json');
  });
}
