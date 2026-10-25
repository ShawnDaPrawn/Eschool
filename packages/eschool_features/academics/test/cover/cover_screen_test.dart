import 'dart:async';
import 'dart:convert';
import 'package:eschool_academics/eschool_academics.dart';
import 'package:eschool_core/eschool_core.dart';
import 'package:flutter/material.dart';
import 'package:flutter_test/flutter_test.dart';

class FakeCover implements CoverRepository {
  @override
  int schoolId = 1;
  @override
  int accountId = 2;
  @override
  bool isActive = true;
  bool failWrite = false, deny = false;
  final writes = <String>[];
  Completer<CoverConfiguration>? delayed;
  @override
  Future<CoverConfiguration> configuration(
    String from,
    String to, {
    int? year,
    int page = 1,
  }) async {
    if (deny) {
      throw ApiException('Denied', statusCode: 403);
    }
    if (delayed != null) {
      return delayed!.future;
    }
    return config();
  }

  CoverConfiguration config() => CoverConfiguration.fromJson({
    'session_year_id': 1,
    'year_name': '2026',
    'can_manage': false,
    'teachers': [
      {'id': 4, 'name': 'Teacher One'},
    ],
    'current_page': 1,
    'last_page': 1,
    'total': 0,
    'absences': [],
  });
  @override
  Future<List<CoverOccurrence>> mine(int year, String from, String to) async =>
      [];
  @override
  Future<CoverAbsence> absence(int id) async => throw UnimplementedError();
  @override
  Future<CoverCandidates> candidates(int id) async =>
      throw UnimplementedError();
  @override
  Future<void> write(
    String action,
    int? id,
    Map<String, dynamic> payload,
  ) async {
    writes.add(jsonEncode(payload));
    if (failWrite) {
      failWrite = false;
      throw ApiException('Connection lost', isTransportFailure: true);
    }
  }
}

void main() {
  testWidgets('read-only module keeps browsing but disables cover mutations', (
    tester,
  ) async {
    final repo = FakeCover();
    await tester.pumpWidget(
      MaterialApp(
        home: CoverScreen(repository: repo, writable: () => false),
      ),
    );
    await tester.pumpAndSettle();
    await tester.tap(find.text('Cover requests'));
    await tester.pumpAndSettle();
    expect(find.text('Teacher One'), findsOneWidget);
    expect(tester.widget<TextField>(find.byType(TextField)).readOnly, isTrue);
    expect(
      tester
          .widget<FilledButton>(
            find.widgetWithText(FilledButton, 'Request cover'),
          )
          .onPressed,
      isNull,
    );
    expect(repo.writes, isEmpty);
  });
  for (final width in [390.0, 820.0, 1280.0]) {
    testWidgets('request form uses named teacher and fits $width', (
      tester,
    ) async {
      tester.view.physicalSize = Size(width, 1000);
      tester.view.devicePixelRatio = 1;
      addTearDown(tester.view.resetPhysicalSize);
      addTearDown(tester.view.resetDevicePixelRatio);
      final repo = FakeCover();
      await tester.pumpWidget(MaterialApp(home: CoverScreen(repository: repo)));
      await tester.pumpAndSettle();
      await tester.tap(find.text('Cover requests'));
      await tester.pumpAndSettle();
      expect(find.text('Teacher One'), findsOneWidget);
      expect(find.text('Private reason (optional)'), findsOneWidget);
      expect(tester.takeException(), isNull);
    });
  }
  testWidgets(
    'unknown write freezes draft and retries identical UUID payload',
    (tester) async {
      final repo = FakeCover()..failWrite = true;
      await tester.pumpWidget(MaterialApp(home: CoverScreen(repository: repo)));
      await tester.pumpAndSettle();
      await tester.tap(find.text('Cover requests'));
      await tester.pumpAndSettle();
      await tester.enterText(find.byType(TextField), 'Private appointment');
      tester.testTextInput.hide();
      await tester.pumpAndSettle();
      await tester.ensureVisible(
        find.widgetWithText(FilledButton, 'Request cover'),
      );
      await tester.pumpAndSettle();
      await tester.tap(find.widgetWithText(FilledButton, 'Request cover'));
      await tester.pumpAndSettle();
      expect(repo.writes, hasLength(1));
      expect(tester.widget<TextField>(find.byType(TextField)).readOnly, isTrue);
      await tester.tap(find.widgetWithText(FilledButton, 'Retry same change'));
      await tester.pumpAndSettle();
      expect(repo.writes, hasLength(2));
      expect(repo.writes[1], repo.writes[0]);
      expect(
        tester.widget<TextField>(find.byType(TextField)).controller!.text,
        isEmpty,
      );
    },
  );
  testWidgets(
    'resume rechecks access before exposing private draft and clears a refusal',
    (tester) async {
      final repo = FakeCover();
      await tester.pumpWidget(MaterialApp(home: CoverScreen(repository: repo)));
      await tester.pumpAndSettle();
      await tester.tap(find.text('Cover requests'));
      await tester.pumpAndSettle();
      await tester.enterText(find.byType(TextField), 'Private appointment');
      tester.binding.handleAppLifecycleStateChanged(AppLifecycleState.inactive);
      await tester.pump();
      expect(find.text('Private appointment'), findsNothing);
      repo.deny = true;
      tester.binding.handleAppLifecycleStateChanged(AppLifecycleState.resumed);
      await tester.pumpAndSettle();
      expect(find.byType(TextField), findsNothing);
      expect(find.textContaining('Your access changed'), findsOneWidget);
    },
  );
  testWidgets('old account response cannot replace a new repository', (
    tester,
  ) async {
    final first = FakeCover()..delayed = Completer<CoverConfiguration>();
    final second = FakeCover()..accountId = 99;
    await tester.pumpWidget(
      MaterialApp(
        home: CoverScreen(key: const Key('cover'), repository: first),
      ),
    );
    await tester.pump();
    await tester.pumpWidget(
      MaterialApp(
        home: CoverScreen(key: const Key('cover'), repository: second),
      ),
    );
    await tester.pumpAndSettle();
    first.delayed!.complete(
      CoverConfiguration.fromJson({
        ...first.config().toJson(),
        'year_name': 'Old private school',
      }),
    );
    await tester.pumpAndSettle();
    expect(find.text('Old private school'), findsNothing);
    expect(find.text('2026'), findsOneWidget);
  });
}
