import 'package:dio/dio.dart';
import 'package:eschool_teacher/app/app.dart';
import 'package:eschool_teacher/utils/api.dart';
import 'package:eschool_teacher/utils/hiveBoxKeys.dart';
import 'package:eschool_core/testing.dart';
import 'package:eschool_notifications/eschool_notifications.dart';
import 'package:flutter/widgets.dart';
import 'package:flutter_test/flutter_test.dart';
import 'package:hive_flutter/hive_flutter.dart';

bool _prepared = false;

/// Starts the app signed out, against a [FakeApi] answering from [routes]:
/// no server, no Firebase, no push. Works on a phone and in Chrome.
Future<FakeApi> startSmokeApp(
  WidgetTester tester,
  Map<String, Object> routes,
) async {
  final FakeApi api = FakeApi(routes: routes);
  Api.debugCreateDio = () => Dio()..httpClientAdapter = api;
  NotificationUtility.enabled = false;
  if (!_prepared) {
    await prepareApp(useFirebase: false);
    _prepared = true;
  }
  // Whatever an earlier flow left on this device: start signed out.
  for (final String box in [authBoxKey, settingsBoxKey]) {
    await Hive.box(box).clear();
  }
  await tester.pumpWidget(const MyApp());
  return api;
}

/// Pumps frames until [finder] finds something, for screens that never
/// settle (shimmers, spinners and looping animations).
Future<void> pumpUntilFound(
  WidgetTester tester,
  Finder finder, {
  Duration timeout = const Duration(seconds: 30),
}) async {
  final DateTime end = DateTime.now().add(timeout);
  while (finder.evaluate().isEmpty) {
    if (DateTime.now().isAfter(end)) {
      throw TestFailure('Timed out waiting for $finder');
    }
    await tester.pump(const Duration(milliseconds: 100));
  }
  // Let the route transition that brought it in finish.
  await tester.pump(const Duration(milliseconds: 600));
}

/// Types into the [index]th text field on screen.
Future<void> enterField(WidgetTester tester, int index, String text) async {
  await tester.enterText(find.byType(EditableText).at(index), text);
  await tester.pump();
}

/// Taps [finder] once it can take a tap: hides the keyboard (which can cover
/// a button on a phone), scrolls the target into view if it is in a list,
/// and waits for it to be hit-testable, so a slow emulator does not tap a
/// screen that is still moving.
Future<void> tapWhenReady(WidgetTester tester, Finder finder) async {
  FocusManager.instance.primaryFocus?.unfocus();
  await tester.pump(const Duration(milliseconds: 300));
  await pumpUntilFound(tester, finder);
  final end = DateTime.now().add(const Duration(seconds: 30));
  while (true) {
    final target = finder.hitTestable();
    if (target.evaluate().isNotEmpty) {
      // No frame pump between checking this target and tapping it: a route can
      // remove a previously found button during the settling delay.
      await tester.tap(target.first);
      return;
    }
    if (DateTime.now().isAfter(end)) {
      throw TestFailure('Timed out waiting to tap $finder');
    }
    if (finder.evaluate().isNotEmpty) {
      await tester.ensureVisible(finder.first);
    }
    await tester.pump(const Duration(milliseconds: 100));
  }
}
