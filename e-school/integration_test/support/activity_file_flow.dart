import 'package:eschool_academics/testing.dart';
import 'package:eschool_core/testing.dart';
import 'package:flutter/material.dart';
import 'package:flutter_test/flutter_test.dart';

import 'smoke.dart' show tapWhenReady, pumpUntilFound;

Future<void> verifyMaterialFiles(
  WidgetTester tester,
  MaterialFileClientFixture fixture,
  FakeApi api, {
  required int account,
  required bool bearer,
  bool resetsRoute = false,
}) async {
  await tapWhenReady(tester, find.text('Activity groups'));
  await tapWhenReady(tester, find.text('Hockey'));
  await tapWhenReady(tester, find.text('Documents'));
  await pumpUntilFound(tester, find.text('Private synthetic worksheet.jpg'));
  final cacheEntries = PaintingBinding.instance.imageCache.currentSize;
  await tapWhenReady(tester, find.text('View document'));
  final raw = find.byWidgetPredicate((w) => w is RawImage && w.image != null);
  await pumpUntilFound(tester, raw);
  final decoded = tester.widget<RawImage>(raw).image!;
  expect(decoded.width, 2);
  expect(decoded.height, 2);
  expect(PaintingBinding.instance.imageCache.currentSize, cacheEntries);
  await tapWhenReady(tester, find.byTooltip('Close'));
  await pumpUntilFound(tester, find.text('View document'));
  expect(decoded.debugDisposed, isTrue);
  fixture.pdf = true;
  await tapWhenReady(tester, find.text('Refresh documents'));
  await pumpUntilFound(tester, find.text('Private synthetic worksheet.pdf'));
  await tapWhenReady(tester, find.text('View document'));
  await pumpUntilFound(
    tester,
    find.byKey(const ValueKey('profile-file-ready')),
  );
  await tapWhenReady(tester, find.byTooltip('Close'));
  await pumpUntilFound(tester, find.text('View document'));
  if (fixture.staff) {
    await tapWhenReady(tester, find.text('Remove document'));
    await tapWhenReady(tester, find.text('Remove document'));
    await pumpUntilFound(tester, find.text('No documents have been attached.'));
    expect(fixture.removalEffects, 1);
  } else {
    expect(find.text('Upload document'), findsNothing);
    expect(find.text('Remove document'), findsNothing);
  }
  await tester.pageBack();
  await pumpUntilFound(tester, find.text('Fixture note'));
  fixture.allowed = false;
  tester.binding.handleAppLifecycleStateChanged(AppLifecycleState.inactive);
  await tester.pump();
  tester.binding.handleAppLifecycleStateChanged(AppLifecycleState.resumed);
  await pumpUntilFound(
    tester,
    find.text(
      resetsRoute ? 'Your school activities' : 'Not available for this account',
    ),
  );
  expect(find.text('Fixture note', skipOffstage: false), findsNothing);
  expect(
    find.text('Private synthetic worksheet.jpg', skipOffstage: false),
    findsNothing,
  );
  expect(
    find.text('Private synthetic worksheet.pdf', skipOffstage: false),
    findsNothing,
  );
  expect(find.byType(RawImage), findsNothing);
  final requests = api.requests.where(
    (r) => r.uri.path.contains('/activities/'),
  );
  expect(requests, isNotEmpty);
  expect(
    requests.every(
      (r) =>
          r.headers['X-School-Id'] == '1' &&
          r.headers['X-Account-Id'] == '$account' &&
          (bearer
              ? '${r.headers['Authorization']}'.startsWith('Bearer ')
              : r.headers['Authorization'] == null),
    ),
    isTrue,
  );
  expect(tester.takeException(), isNull);
}
