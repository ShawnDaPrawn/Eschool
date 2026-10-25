import 'dart:async';
import 'package:eschool_academics/eschool_academics.dart';
import 'package:eschool_core/eschool_core.dart';
import 'package:flutter/material.dart';
import 'package:flutter_test/flutter_test.dart';

class InboxFixture implements PrivateStaffNoticeRepository {
  bool active = true, readonly = false, deny = false;
  int failedWrites = 0;
  final writes = <int>[];
  Completer<StaffPrivateNoticePage>? pending;
  @override
  bool get isActive => active;
  StaffPrivateNoticePage page(int page) => StaffPrivateNoticePage(
    schoolId: 1,
    readerId: 7,
    writable: !readonly,
    items: [
      StaffPrivateNotice(
        id: 19,
        feature: 'wellbeing',
        title: 'Private school update',
        message: 'Open your private area.',
        writable: !readonly,
        readAt: null,
      ),
    ],
    page: page,
    lastPage: 2,
    unreadCount: 1,
  );
  @override
  Future<StaffPrivateNoticePage> notices(int number) async {
    if (deny) throw ApiException('Denied', statusCode: 403);
    return pending?.future ?? Future.value(page(number));
  }

  @override
  Future<StaffPrivateNoticeRead> markRead(int id) async {
    writes.add(id);
    if (deny) throw ApiException('Denied', statusCode: 403);
    if (failedWrites-- > 0) throw ApiException('Unknown outcome');
    return StaffPrivateNoticeRead(
      id: id,
      schoolId: 1,
      readerId: 7,
      readAt: DateTime.utc(2026, 2, 10),
    );
  }
}

void main() {
  Widget screen(
    InboxFixture repo, {
    Future<void> Function(String)? open,
    bool Function(String)? canOpen,
  }) => MaterialApp(
    home: PrivateStaffInboxScreen(
      repository: repo,
      openFeature: open ?? (_) async {},
      canOpen: canOpen ?? (_) => true,
    ),
  );
  for (final width in [390.0, 820.0, 1280.0]) {
    testWidgets('generic staff metadata and idempotent marker fit $width', (
      tester,
    ) async {
      tester.view.physicalSize = Size(width, 1000);
      tester.view.devicePixelRatio = 1;
      addTearDown(tester.view.resetPhysicalSize);
      addTearDown(tester.view.resetDevicePixelRatio);
      final repo = InboxFixture();
      String? opened;
      await tester.pumpWidget(
        screen(
          repo,
          open: (feature) async {
            opened = feature;
          },
        ),
      );
      await tester.pumpAndSettle();
      expect(find.text('Unread private updates: 1'), findsOneWidget);
      await tester.tap(find.byKey(const ValueKey('private-staff-notice-19')));
      await tester.pumpAndSettle();
      expect(repo.writes, [19]);
      expect(opened, 'wellbeing');
      expect(tester.takeException(), isNull);
    });
  }
  testWidgets(
    'unknown read marker retries only the same notice and does not open early',
    (tester) async {
      final repo = InboxFixture()..failedWrites = 1;
      var opens = 0;
      await tester.pumpWidget(
        screen(
          repo,
          open: (_) async {
            opens++;
          },
        ),
      );
      await tester.pumpAndSettle();
      await tester.tap(find.byKey(const ValueKey('private-staff-notice-19')));
      await tester.pumpAndSettle();
      expect(opens, 0);
      expect(repo.writes, [19]);
      await tester.tap(find.text('Retry the same read confirmation'));
      await tester.pumpAndSettle();
      expect(opens, 1);
      expect(repo.writes, [19, 19]);
    },
  );
  testWidgets(
    'read-only notices still open their permitted feature without marker writes',
    (tester) async {
      final repo = InboxFixture()..readonly = true;
      var opens = 0;
      await tester.pumpWidget(
        screen(
          repo,
          open: (_) async {
            opens++;
          },
        ),
      );
      await tester.pumpAndSettle();
      await tester.tap(find.byKey(const ValueKey('private-staff-notice-19')));
      await tester.pumpAndSettle();
      expect(opens, 1);
      expect(repo.writes, isEmpty);
    },
  );
  testWidgets('source refusal erases metadata and cannot retain retry intent', (
    tester,
  ) async {
    final repo = InboxFixture();
    await tester.pumpWidget(screen(repo));
    await tester.pumpAndSettle();
    repo.deny = true;
    await tester.tap(find.text('Refresh private updates'));
    await tester.pumpAndSettle();
    expect(find.byKey(const ValueKey('private-staff-notice-19')), findsNothing);
    expect(find.text('Retry the same read confirmation'), findsNothing);
    expect(
      find.text(
        'Private updates are no longer available. Refresh staff access.',
      ),
      findsOneWidget,
    );
  });
  testWidgets('late metadata after background stays erased even on resume', (
    tester,
  ) async {
    final repo = InboxFixture()..pending = Completer<StaffPrivateNoticePage>();
    await tester.pumpWidget(screen(repo));
    await tester.pump();
    tester.binding.handleAppLifecycleStateChanged(AppLifecycleState.inactive);
    repo.pending!.complete(repo.page(1));
    await tester.pumpAndSettle();
    expect(find.text('Private school update'), findsNothing);
    tester.binding.handleAppLifecycleStateChanged(AppLifecycleState.resumed);
    await tester.pumpAndSettle();
    expect(find.text('Private school update'), findsNothing);
  });
  testWidgets('unknown or withdrawn feature capability never navigates', (
    tester,
  ) async {
    final repo = InboxFixture();
    var opens = 0;
    await tester.pumpWidget(
      screen(
        repo,
        canOpen: (_) => false,
        open: (_) async {
          opens++;
        },
      ),
    );
    await tester.pumpAndSettle();
    await tester.tap(find.byKey(const ValueKey('private-staff-notice-19')));
    await tester.pumpAndSettle();
    expect(opens, 0);
    expect(repo.writes, isEmpty);
  });
  testWidgets(
    'confirmed marker plus navigation failure never offers an unknown-write retry',
    (tester) async {
      final repo = InboxFixture();
      await tester.pumpWidget(
        screen(
          repo,
          open: (_) async {
            throw ApiException('Navigation failed');
          },
        ),
      );
      await tester.pumpAndSettle();
      await tester.tap(find.byKey(const ValueKey('private-staff-notice-19')));
      await tester.pumpAndSettle();
      expect(repo.writes, [19]);
      expect(find.text('Retry the same read confirmation'), findsNothing);
    },
  );
}
