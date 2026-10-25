import 'dart:io';

import 'package:eschool_teacher/facilitator/facilitator_app.dart';
import 'package:hive/hive.dart';

import 'facilitator_test_fixture.dart';

import 'package:eschool_teacher/app/staffBuildIdentity.dart';
import 'package:eschool_teacher/app/staffBuildUnavailable.dart';
import 'package:eschool_teacher/app/app.dart';
import 'package:flutter_test/flutter_test.dart';
import 'package:flutter/material.dart';
import 'package:flutter/services.dart';

void main() {
  test('legacy teacher startup is preserved', () async {
    var starts = 0;
    await startStaffBuild(
      identity: StaffBuildIdentity.resolve(),
      startTeacher: () async {
        starts++;
      },
      startUnavailable: (_) => fail('teacher should start'),
    );
    expect(starts, 1);
    expect(
      StaffBuildIdentity.resolve(
        nativeFlavor: 'teacher',
        dartFlavor: 'teacher',
      ).canStartTeacher,
      isTrue,
    );
  });

  test('facilitator calls only its dedicated bootstrap', () async {
    var starts = 0;
    await startStaffBuild(
      identity: StaffBuildIdentity.resolve(
        nativeFlavor: 'facilitator',
        dartFlavor: 'facilitator',
      ),
      startTeacher: () async => fail('teacher bootstrap ran'),
      startFacilitator: () async {
        starts++;
      },
      startUnavailable: (_) => fail('valid facilitator refused'),
    );
    expect(starts, 1);
    for (final native in ['teacher', 'vendor', 'unknown']) {
      await startStaffBuild(
        identity: StaffBuildIdentity.resolve(
          nativeFlavor: native,
          dartFlavor: 'facilitator',
        ),
        startTeacher: () async => fail('mismatch startedteacher'),
        startFacilitator: () async => fail('mismatch startedfacilitator'),
        startUnavailable: (_) {},
      );
    }
  });

  test('native identity and build defines must agree', () {
    for (final native in ['teacher', 'facilitator', 'vendor']) {
      for (final defined in ['teacher', 'facilitator', 'vendor']) {
        final identity = StaffBuildIdentity.resolve(
          nativeFlavor: native,
          dartFlavor: defined,
        );
        expect(identity.valid, native == defined);
        expect(
          identity.canStartTeacher,
          native == 'teacher' && defined == 'teacher',
        );
      }
    }
  });

  test(
    'reserved and unknown identities cannot call teacher bootstrap',
    () async {
      for (final flavor in [
        'facilitator',
        'vendor',
        'unknown',
        'Teacher',
        ' teacher',
        '',
      ]) {
        if (flavor.isEmpty) continue;
        var refused = 0;
        await startStaffBuild(
          identity: StaffBuildIdentity.resolve(nativeFlavor: flavor),
          startTeacher: () async =>
              fail('nonteacher initialized authenticated shell'),
          startUnavailable: (_) {
            refused++;
          },
        );
        expect(refused, 1);
      }
      expect(
        StaffBuildIdentity.resolve(
          nativeFlavor: 'teacher',
          dartFlavor: 'facilitator',
        ).canStartTeacher,
        isFalse,
      );
    },
  );

  test('native flavor alone selects the reserved shell', () {
    expect(
      StaffBuildIdentity.resolve(nativeFlavor: 'facilitator').name,
      'facilitator',
    );
    expect(
      StaffBuildIdentity.resolve(dartFlavor: 'vendor').displayName,
      'NavigateEd Vendor',
    );
  });

  for (final width in [390.0, 1280.0]) {
    testWidgets('reserved shell has no authenticated controls at $width', (
      tester,
    ) async {
      tester.view.physicalSize = Size(width, 900);
      tester.view.devicePixelRatio = 1;
      addTearDown(tester.view.resetPhysicalSize);
      addTearDown(tester.view.resetDevicePixelRatio);
      for (final flavor in ['facilitator', 'vendor']) {
        await tester.pumpWidget(
          StaffBuildUnavailable(
            identity: StaffBuildIdentity.resolve(nativeFlavor: flavor),
          ),
        );
        await tester.pumpAndSettle();
        expect(
          find.text(
            'NavigateEd ${flavor[0].toUpperCase()}${flavor.substring(1)}',
          ),
          findsOneWidget,
        );
        expect(find.textContaining('not available yet'), findsOneWidget);
        expect(find.byType(TextField), findsNothing);
        expect(tester.takeException(), isNull);
      }
    });
  }

  if (!StaffBuildIdentity.current.canStartTeacher) {
    testWidgets('real entrypoint refuses teacher initialization', (
      tester,
    ) async {
      if (StaffBuildIdentity.current.canStartFacilitator) {
        staffTestLabels(tester);
        final directory = await tester.runAsync(
          () => Directory.systemTemp.createTemp('staff-entry-storage-'),
        );
        const channel = MethodChannel('plugins.flutter.io/path_provider');
        tester.binding.defaultBinaryMessenger.setMockMethodCallHandler(
          channel,
          (call) async => switch (call.method) {
            'getApplicationSupportDirectory' => '${directory!.path}/support',
            'getApplicationDocumentsDirectory' =>
              '${directory!.path}/documents',
            _ => throw PlatformException(code: 'unexpected-path-request'),
          },
        );
        addTearDown(() async {
          await tester.runAsync(() async {
            await Hive.close();
            await directory!.delete(recursive: true);
          });
          tester.binding.defaultBinaryMessenger.setMockMethodCallHandler(
            channel,
            null,
          );
        });
      }
      // Native filesystem/plugin futures must run outside widget fake time.
      await tester.runAsync(() => initializeApp());
      await tester.pumpAndSettle();
      expect(
        find.byType(
          StaffBuildIdentity.current.canStartFacilitator
              ? FacilitatorApp
              : StaffBuildUnavailable,
        ),
        findsOneWidget,
      );
      expect(Hive.isBoxOpen('authBox'), isFalse);
      expect(Hive.isBoxOpen('settings'), isFalse);
      expect(tester.takeException(), isNull);
    });
  }
}
