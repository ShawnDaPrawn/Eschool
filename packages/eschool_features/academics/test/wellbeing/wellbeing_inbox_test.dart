import 'dart:async';
import 'package:eschool_academics/eschool_academics.dart';
import 'package:eschool_academics/src/wellbeing/wellbeing_inbox_panel.dart';
import 'package:flutter/material.dart';
import 'package:flutter_test/flutter_test.dart';
import 'wellbeing_fake.dart';

class Notices extends FakeWellbeing {
  Completer<WellbeingNoticePage>? pending;
  int? marked;
  @override
  Future<WellbeingNoticePage> notices(int page) async {
    guard();
    if (pending != null) return pending!.future;
    return WellbeingNoticePage(
      items: [
        WellbeingNotice(
          id: 7,
          title: 'Private school update',
          message: 'Generic update',
          link: '/wellbeing',
          createdAt: DateTime.utc(2026, 2, 10),
          readAt: null,
        ),
      ],
      page: 1,
      lastPage: 1,
      unreadCount: 1,
    );
  }

  @override
  Future<WellbeingNoticeReadReceipt> readNotice(int id) async {
    guard();
    marked = id;
    return super.readNotice(id);
  }
}

void main() {
  for (final width in [390.0, 820.0, 1280.0]) {
    testWidgets('private metadata and read receipt fit $width', (tester) async {
      tester.view.physicalSize = Size(width, 1000);
      tester.view.devicePixelRatio = 1;
      addTearDown(tester.view.resetPhysicalSize);
      addTearDown(tester.view.resetDevicePixelRatio);
      final repo = Notices();
      var returned = false;
      await tester.pumpWidget(
        MaterialApp(
          home: Scaffold(
            body: WellbeingInboxPanel(
              repository: repo,
              onReturn: () async {
                returned = true;
              },
              onDenied: () {},
            ),
          ),
        ),
      );
      await tester.pumpAndSettle();
      expect(find.text('Unread private updates: 1'), findsOneWidget);
      await tester.tap(find.byKey(const ValueKey('wellbeing-notice-7')));
      await tester.pumpAndSettle();
      expect(repo.marked, 7);
      expect(returned, isTrue);
      expect(tester.takeException(), isNull);
    });
  }
  testWidgets(
    'withdrawn source clears metadata before notifying owning reader',
    (tester) async {
      final repo = Notices();
      var denied = false;
      await tester.pumpWidget(
        MaterialApp(
          home: Scaffold(
            body: WellbeingInboxPanel(
              repository: repo,
              onReturn: () async {},
              onDenied: () {
                denied = true;
              },
            ),
          ),
        ),
      );
      await tester.pumpAndSettle();
      repo.deny = true;
      await tester.tap(find.text('Refresh private updates'));
      await tester.pumpAndSettle();
      expect(denied, isTrue);
      expect(find.byKey(const ValueKey('wellbeing-notice-7')), findsNothing);
    },
  );
  testWidgets('late inbox response after background is discarded', (
    tester,
  ) async {
    final repo = Notices()..pending = Completer<WellbeingNoticePage>();
    await tester.pumpWidget(
      MaterialApp(
        home: Scaffold(
          body: WellbeingInboxPanel(
            repository: repo,
            onReturn: () async {},
            onDenied: () {},
          ),
        ),
      ),
    );
    await tester.pump();
    tester.binding.handleAppLifecycleStateChanged(AppLifecycleState.inactive);
    repo.pending!.complete(
      WellbeingNoticePage(items: [], page: 1, lastPage: 1, unreadCount: 41),
    );
    await tester.pumpAndSettle();
    expect(find.textContaining('41'), findsNothing);
    tester.binding.handleAppLifecycleStateChanged(AppLifecycleState.resumed);
  });
}
