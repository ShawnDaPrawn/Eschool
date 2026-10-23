import 'dart:js_interop';

import 'package:flutter_test/flutter_test.dart';

@JS('document.querySelectorAll')
external _Frames _queryFrames(String selector);

extension type _Frames(JSObject _) implements JSObject {
  external int get length;
  external _Frame item(int index);
}

extension type _Frame(JSObject _) implements JSObject {
  external _Document? get contentDocument;
}

extension type _Document(JSObject _) implements JSObject {
  external _Element? getElementById(String id);
}

extension type _Element(JSObject _) implements JSObject {
  external void click();
}

// WidgetTester pointer events cannot enter an HTML platform-view iframe.
// Click its actual DOM element so the JavaScript-to-Dart bridge is exercised.
Future<void> tapMathOption(
  WidgetTester tester,
  Finder option,
  String id,
) async {
  final frames = _queryFrames('iframe');
  for (var i = 0; i < frames.length; i++) {
    final element = frames.item(i).contentDocument?.getElementById(id);
    if (element != null) {
      element.click();
      await tester.pump(const Duration(milliseconds: 200));
      return;
    }
  }
  throw StateError('Rendered maths option $id was not found in a TeX iframe');
}
