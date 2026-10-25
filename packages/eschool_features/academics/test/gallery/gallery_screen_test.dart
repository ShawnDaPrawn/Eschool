import 'dart:async';
import 'dart:convert';
import 'dart:typed_data';
import 'package:eschool_academics/eschool_academics.dart';
import 'package:eschool_api/eschool_api.dart';
import 'package:eschool_core/eschool_core.dart';
import 'package:flutter/material.dart';
import 'package:flutter_test/flutter_test.dart';

class Photos implements PrivateGalleryRepository {
  @override
  int schoolId = 1, accountId = 12;
  @override
  bool isActive = true;
  bool approve = false, deny = false, offline = false, removed = false;
  final writes = <Map<String, dynamic>>[],
      familyReads = <int>[],
      groupReads = <int>[];
  Completer<GalleryFamilyPage>? delayed;
  Map<String, dynamic> config() => {
    'school_id': 1,
    'account_id': 12,
    'session_year_id': 7,
    'today': '2026-02-10',
    'years': [
      {
        'id': 7,
        'name': '2026',
        'starts_on': '2026-01-01',
        'ends_on': '2026-12-31',
      },
    ],
    'children': [
      for (final id in [2, 3])
        {
          'id': id,
          'name': 'Synthetic learner $id',
          'handle': 'learner$id',
          'can_approve': approve,
          'archived': false,
        },
    ],
    'groups': [
      for (final id in [2, 3])
        {
          'id': id,
          'name': 'Private group $id',
          'session_year_id': 7,
          'can_upload': false,
          'learner_ids': [id],
        },
    ],
    'guardian_required': true,
    'settings_revision': 1,
    'can_manage': false,
    'can_write': true,
  };
  Map<String, dynamic> photoData() => {
    'id': 8,
    'public_id': 'synthetic-photo',
    'caption': 'Private family caption',
    'captured_on': '2026-02-09',
    'school_year': '2026',
    'session_year_id': 7,
    'activity_group_id': 2,
    'scope': 'group',
    'width': 1,
    'height': 1,
    'active': true,
    'revision': 1,
  };
  GalleryFamilyPage page() => GalleryFamilyPage.fromJson({
    'items': removed
        ? []
        : [
            {
              'tag_id': 9,
              'tag_revision': 1,
              'status': 'approved',
              'retained_on_transfer': false,
              'photo': photoData(),
            },
          ],
    'total': removed ? 0 : 1,
    'next_before': null,
  });
  @override
  Future<GalleryConfiguration> configuration() async {
    if (deny)
      throw ApiException(
        'Photo access withdrawn',
        statusCode: 403,
        isApiMessage: true,
      );
    return GalleryConfiguration.fromJson(config());
  }

  @override
  Future<GalleryFamilyPage> family(
    int learner, {
    bool pending = false,
    int? before,
    int? year,
  }) async {
    familyReads.add(learner);
    return delayed?.future ?? page();
  }

  @override
  Future<GalleryGroupPage> group(int group, {int? before}) async {
    groupReads.add(group);
    return GalleryGroupPage.fromJson({
      'items': [photoData()],
      'next_before': null,
    });
  }

  @override
  Future<Uint8List> photo(
    int id, {
    bool thumbnail = true,
  }) async => base64Decode(
    'iVBORw0KGgoAAAANSUhEUgAAAAEAAAABCAQAAAC1HAwCAAAAC0lEQVR42mNk+A8AAQUBAScY42YAAAAASUVORK5CYII=',
  );
  @override
  Future<GalleryReference> upload(
    Map<String, dynamic> payload,
    Uint8List bytes,
  ) async {
    writes.add({'action': 'upload', ...payload, 'pixels': bytes.toList()});
    if (offline) {
      offline = false;
      throw ApiException('Lost response', isTransportFailure: true);
    }
    return GalleryReference(id: 8, revision: 1);
  }

  @override
  Future<GalleryReference> change(
    String action,
    int id,
    Map<String, dynamic> payload,
  ) async {
    writes.add({'action': action, 'id': id, ...payload});
    if (offline) {
      offline = false;
      throw ApiException('Lost response', isTransportFailure: true);
    }
    removed = action == 'remove';
    return GalleryReference(id: id, revision: 2);
  }
}

Future<void> show(WidgetTester tester, Photos repo, {int learner = 2}) async {
  await tester.pumpWidget(
    MaterialApp(
      home: PrivateGalleryScreen(
        repository: repo,
        family: true,
        learnerId: learner,
      ),
    ),
  );
  await tester.pumpAndSettle();
}

Future<void> tap(WidgetTester tester, Finder finder) async {
  await tester.scrollUntilVisible(
    finder,
    250,
    scrollable: find.byType(Scrollable).first,
    maxScrolls: 20,
  );
  await tester.ensureVisible(finder);
  await tester.pumpAndSettle();
  await tester.tap(finder);
  await tester.pumpAndSettle();
}

void main() {
  testWidgets(
    'a bounded picked upload keeps its exact private body and pixels across an unknown retry',
    (tester) async {
      final repo = Photos()..offline = true;
      await tester.pumpWidget(
        MaterialApp(
          home: PrivateGalleryScreen(
            repository: repo,
            family: true,
            learnerId: 2,
            selectPhoto: () async =>
                Uint8List.fromList([0xff, 0xd8, 0xff, 0xd9]),
          ),
        ),
      );
      await tester.pumpAndSettle();
      await tester.enterText(
        find.byType(TextField).first,
        'Private upload caption',
      );
      tester.testTextInput.hide();
      await tester.pumpAndSettle();
      await tap(tester, find.text('Choose JPEG or PNG'));
      await tap(tester, find.text('Upload photo'));
      expect(repo.writes.single['caption'], 'Private upload caption');
      expect(repo.writes.single['learner_id'], 2);
      expect(repo.writes.single['scope'], 'family');
      await tap(tester, find.text('Retry same change'));
      expect(repo.writes.first, repo.writes.last);
    },
  );
  for (final width in [390.0, 820.0, 1280.0]) {
    testWidgets('private family photo and optional tag removal at $width', (
      tester,
    ) async {
      tester.view.physicalSize = Size(width, 1000);
      tester.view.devicePixelRatio = 1;
      addTearDown(tester.view.resetPhysicalSize);
      addTearDown(tester.view.resetDevicePixelRatio);
      final repo = Photos();
      await show(tester, repo);
      await tap(tester, find.text('Remove my tag'));
      expect(repo.writes.single['action'], 'remove');
      expect(find.text('Private family caption'), findsNothing);
      expect(tester.takeException(), isNull);
    });
  }
  testWidgets(
    'default learner cannot approve pending tags but can remove own proposal',
    (tester) async {
      final repo = Photos();
      await show(tester, repo);
      await tap(tester, find.text('Pending tags'));
      expect(find.text('Approve photo tag'), findsNothing);
      await tap(tester, find.text('Remove my tag'));
      expect(repo.writes.single['action'], 'remove');
    },
  );
  testWidgets(
    'fresh explicit approval capability permits the exact pending decision',
    (tester) async {
      final repo = Photos()..approve = true;
      await show(tester, repo);
      await tap(tester, find.text('Pending tags'));
      await tap(tester, find.text('Approve photo tag'));
      expect(repo.writes.single['decision'], 'approve');
      expect(repo.writes.single['revision'], 1);
    },
  );
  testWidgets(
    'unknown mutation freezes intent and retries identical UUID and revision',
    (tester) async {
      final repo = Photos()..offline = true;
      await show(tester, repo);
      await tap(tester, find.text('Remove my tag'));
      expect(repo.writes, hasLength(1));
      await tap(tester, find.text('Retry same change'));
      expect(repo.writes, hasLength(2));
      expect(repo.writes.first, repo.writes.last);
      expect(find.text('Private family caption'), findsNothing);
    },
  );
  testWidgets(
    'background clears rendered photos but retains an unresolved exact mutation',
    (tester) async {
      final repo = Photos()..offline = true;
      await show(tester, repo);
      await tap(tester, find.text('Remove my tag'));
      tester.binding.handleAppLifecycleStateChanged(AppLifecycleState.inactive);
      await tester.pump();
      expect(find.text('Private family caption'), findsNothing);
      tester.binding.handleAppLifecycleStateChanged(AppLifecycleState.resumed);
      await tester.pumpAndSettle();
      await tap(tester, find.text('Retry same change'));
      expect(repo.writes, hasLength(2));
      expect(repo.writes.first, repo.writes.last);
      expect(find.text('Private family caption'), findsNothing);
    },
  );
  testWidgets('a pinned child sees only that child group choices', (
    tester,
  ) async {
    final repo = Photos();
    await show(tester, repo);
    await tap(tester, find.text('My group photos'));
    expect(repo.groupReads, [2]);
    expect(find.text('Private group 3'), findsNothing);
    expect(find.text('Synthetic learner 3'), findsNothing);
  });
  testWidgets('a missing pinned child never falls back to a sibling', (
    tester,
  ) async {
    final repo = Photos();
    await show(tester, repo, learner: 99);
    expect(repo.familyReads, isEmpty);
    expect(find.text('Private family caption'), findsNothing);
    expect(
      find.text('Your photo access changed. Reopen the gallery.'),
      findsOneWidget,
    );
  });
  testWidgets(
    'background erases caption, decoded pixels and unsaved drafts before denied resume',
    (tester) async {
      final repo = Photos();
      await show(tester, repo);
      await tester.enterText(
        find.byType(TextField).first,
        'Private unsaved caption',
      );
      tester.binding.handleAppLifecycleStateChanged(AppLifecycleState.inactive);
      await tester.pump();
      expect(find.text('Private family caption'), findsNothing);
      expect(find.text('Private unsaved caption'), findsNothing);
      expect(find.byType(RawImage), findsNothing);
      repo.deny = true;
      tester.binding.handleAppLifecycleStateChanged(AppLifecycleState.resumed);
      await tester.pumpAndSettle();
      expect(find.text('Photo access withdrawn'), findsOneWidget);
      expect(repo.writes, isEmpty);
    },
  );
  testWidgets('late old-child content cannot refill a replaced gallery', (
    tester,
  ) async {
    final old = Photos()..delayed = Completer<GalleryFamilyPage>();
    await tester.pumpWidget(
      MaterialApp(
        home: PrivateGalleryScreen(repository: old, family: true, learnerId: 2),
      ),
    );
    await tester.pump();
    final next = Photos()..removed = true;
    await show(tester, next, learner: 3);
    old.delayed!.complete(old.page());
    await tester.pumpAndSettle();
    expect(find.text('Private family caption'), findsNothing);
    expect(next.familyReads, [3]);
  });
}
