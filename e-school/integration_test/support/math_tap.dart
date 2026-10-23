import 'package:flutter_test/flutter_test.dart';

Future<void> tapMathOption(WidgetTester tester, Finder option, String id) =>
    tester.tap(option);
