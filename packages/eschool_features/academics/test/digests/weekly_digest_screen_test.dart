import 'dart:async';
import 'package:eschool_academics/eschool_academics.dart';
import 'package:eschool_api/eschool_api.dart';
import 'package:eschool_core/eschool_core.dart';
import 'package:flutter/material.dart';
import 'package:flutter_test/flutter_test.dart';

class Digests implements WeeklyDigestRepository {
  @override
  int schoolId = 1, accountId = 21;
  @override
  bool isActive = true;
  bool deny = false, failOnce = false, accessOffline = false;
  String language = 'en';
  int revision = 0;
  int owner = 11;
  DateTime expiry = DateTime.now().toUtc().add(const Duration(hours: 1));
  Completer<WeeklyDigestAccess>? delayed;
  final reads = <int>[],
      writes = <Map<String, dynamic>>[],
      applied = <String>{};
  @override
  Future<WeeklyDigestAccess> access() async {
    if (accessOffline)
      throw ApiException('Network unavailable', isTransportFailure: true);
    if (deny)
      throw ApiException(
        'Weekly access withdrawn',
        statusCode: 403,
        isApiMessage: true,
      );
    return delayed?.future ??
        WeeklyDigestAccess.fromJson({
          'school_id': schoolId,
          'account_id': accountId,
          'parent': true,
          'can_manage': false,
          'children': [
            {'id': owner, 'name': 'Synthetic learner'},
          ],
          'language': language,
          'preference_revision': revision,
          'languages': ['en', 'zu'],
          'approved_languages': ['en', 'zu'],
        });
  }

  @override
  Future<WeeklyDigestPage> weeks(int learner, {int? before}) async {
    reads.add(learner);
    return WeeklyDigestPage.fromJson({
      'items': [
        {
          'id': 8,
          'learner_id': learner,
          'language': 'en',
          'week_start': '2026-02-09',
          'week_end': '2026-02-15',
          'title': 'Private weekly title',
          'opened': false,
        },
      ],
      'total': 1,
      'next_before': null,
    });
  }

  @override
  Future<WeeklyDigestDetail> detail(int id) async =>
      WeeklyDigestDetail.fromJson({
        'id': id,
        'learner_id': owner,
        'language': 'en',
        'week_start': '2026-02-09',
        'week_end': '2026-02-15',
        'template_version': 1,
        'title': 'Private weekly title',
        'summary': 'Private weekly body: practice 1 of 2.',
        'expires_at': expiry.toIso8601String(),
        'evidence': {
          'marks': [],
          'attendance': {
            'recorded': 4,
            'present': 3,
            'absent': 1,
            'excused': 0,
          },
          'practice': {'total': 2, 'completed': 1},
          'events': [],
          'event_count': 0,
          'action_kind': 'practice',
          'content_version_id': 9,
        },
        'home_action': {
          'kind': 'practice',
          'learner_id': owner,
          'label': 'Open school learning materials together',
          'content_version_id': 9,
        },
      });
  @override
  Future<WeeklyDigestReference> preference(Map<String, dynamic> data) async {
    writes.add(Map.from(data));
    if (applied.add(data['operation_id'] as String)) {
      language = data['language'] as String;
      revision++;
    }
    if (failOnce) {
      failOnce = false;
      throw ApiException('Lost response', isTransportFailure: true);
    }
    return WeeklyDigestReference(id: 1, revision: revision);
  }
}

Future<void> show(
  WidgetTester tester,
  Digests repo, {
  int learner = 11,
  void Function(WeeklyDigestHomeAction)? action,
  DateTime Function()? now,
}) async {
  await tester.pumpWidget(
    MaterialApp(
      home: WeeklyDigestScreen(
        repository: repo,
        learnerId: learner,
        onHomeAction: action ?? (_) {},
        now: now,
      ),
    ),
  );
  await tester.pumpAndSettle();
}

Future<void> tap(WidgetTester tester, Finder finder) async {
  await tester.ensureVisible(finder);
  await tester.pumpAndSettle();
  await tester.tap(finder);
  await tester.pumpAndSettle();
}

void main() {
  for (final width in [390.0, 820.0, 1280.0]) {
    testWidgets(
      'own child weekly summary and one-tap authorized material reference at $width',
      (tester) async {
        tester.view.physicalSize = Size(width, 1000);
        tester.view.devicePixelRatio = 1;
        addTearDown(tester.view.resetPhysicalSize);
        addTearDown(tester.view.resetDevicePixelRatio);
        final repo = Digests();
        WeeklyDigestHomeAction? chosen;
        await show(tester, repo, action: (action) => chosen = action);
        expect(
          find.text('Private weekly body: practice 1 of 2.'),
          findsOneWidget,
        );
        await tap(tester, find.text('Open school learning materials together'));
        expect(chosen?.learnerId, 11);
        expect(chosen?.contentVersionId, 9);
        expect(chosen?.kind, WeeklyDigestHomeActionKindEnum.practice);
        expect(repo.reads, [11]);
        expect(tester.takeException(), isNull);
        await tester.pumpWidget(const SizedBox.shrink());
      },
    );
  }
  testWidgets('a missing pinned child cannot fall back to a sibling', (
    tester,
  ) async {
    final repo = Digests()..owner = 12;
    await show(tester, repo);
    expect(repo.reads, isEmpty);
    expect(find.text('Private weekly title'), findsNothing);
    expect(
      find.text('Your weekly update access changed. Reopen this screen.'),
      findsOneWidget,
    );
    await tester.pumpWidget(const SizedBox.shrink());
  });
  testWidgets(
    'language uncertainty retries the same immutable revision and operation',
    (tester) async {
      final repo = Digests()..failOnce = true;
      await show(tester, repo);
      await tap(tester, find.byType(DropdownButtonFormField<String>));
      await tap(tester, find.text('zu').last);
      expect(repo.writes, hasLength(1));
      await tap(tester, find.text('Retry same language change'));
      expect(repo.writes, hasLength(2));
      expect(repo.writes.first, repo.writes.last);
      expect(repo.revision, 1);
      await tester.pumpWidget(const SizedBox.shrink());
    },
  );
  testWidgets(
    'unresolved language survives a transient resume refresh without restoring the private body',
    (tester) async {
      final repo = Digests()..failOnce = true;
      await show(tester, repo);
      await tap(tester, find.byType(DropdownButtonFormField<String>));
      await tap(tester, find.text('zu').last);
      tester.binding.handleAppLifecycleStateChanged(AppLifecycleState.inactive);
      await tester.pump();
      repo.accessOffline = true;
      tester.binding.handleAppLifecycleStateChanged(AppLifecycleState.resumed);
      await tester.pumpAndSettle();
      expect(find.text('Private weekly body: practice 1 of 2.'), findsNothing);
      repo.accessOffline = false;
      await tap(tester, find.text('Retry same language change'));
      expect(repo.writes, hasLength(2));
      expect(repo.writes.first, repo.writes.last);
      expect(repo.revision, 1);
      await tester.pumpWidget(const SizedBox.shrink());
    },
  );
  testWidgets('background clears summary before a denied resume', (
    tester,
  ) async {
    final repo = Digests();
    await show(tester, repo);
    tester.binding.handleAppLifecycleStateChanged(AppLifecycleState.inactive);
    await tester.pump();
    expect(find.text('Private weekly title'), findsNothing);
    expect(find.text('Private weekly body: practice 1 of 2.'), findsNothing);
    repo.deny = true;
    tester.binding.handleAppLifecycleStateChanged(AppLifecycleState.resumed);
    await tester.pumpAndSettle();
    expect(find.text('Weekly access withdrawn'), findsOneWidget);
    expect(repo.writes, isEmpty);
    await tester.pumpWidget(const SizedBox.shrink());
  });
  testWidgets('expired visible private copy is erased and action cannot fire', (
    tester,
  ) async {
    var now = DateTime.now().toUtc();
    final repo = Digests()..expiry = now.add(const Duration(minutes: 1));
    var actions = 0;
    await show(tester, repo, now: () => now, action: (_) => actions++);
    now = now.add(const Duration(minutes: 1));
    await tester.pump(const Duration(minutes: 1));
    await tester.pump();
    expect(find.text('Private weekly title'), findsNothing);
    expect(find.text('Open school learning materials together'), findsNothing);
    expect(actions, 0);
    await tester.pumpWidget(const SizedBox.shrink());
  });
  testWidgets(
    'a late prior account response cannot repopulate a new child view',
    (tester) async {
      final old = Digests()..delayed = Completer<WeeklyDigestAccess>();
      await tester.pumpWidget(
        MaterialApp(
          home: WeeklyDigestScreen(
            repository: old,
            learnerId: 11,
            onHomeAction: (_) {},
          ),
        ),
      );
      await tester.pump();
      final current = Digests()..owner = 12;
      await show(tester, current, learner: 12);
      old.delayed!.complete(await (Digests()..owner = 11).access());
      await tester.pumpAndSettle();
      expect(current.reads, [12]);
      expect(old.reads, isEmpty);
      expect(find.text('Private weekly title'), findsOneWidget);
      await tester.pumpWidget(const SizedBox.shrink());
    },
  );
}
