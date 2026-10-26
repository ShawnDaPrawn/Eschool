import 'dart:async';
import 'dart:convert';
import 'dart:typed_data';
import 'package:dio/dio.dart';
import 'package:eschool_academics/eschool_academics.dart';
import 'package:eschool_core/eschool_core.dart';
import 'package:eschool_core/testing.dart';
import 'package:eschool_people/eschool_people.dart';
import 'package:flutter/material.dart';
import 'package:flutter_test/flutter_test.dart';
import 'activity_test_repository.dart';

const fileId = '550e8400-e29b-41d4-a716-446655440000';
Map<String, dynamic> fileJson() => {
  'id': fileId,
  'activity_group_id': 7,
  'material_id': 11,
  'name': 'Practice.jpg',
  'mime': 'image/jpeg',
  'bytes': 3,
  'revision': 1,
  'removed': false,
};
Map<String, dynamic> pageJson({bool upload = true, bool remove = true}) => {
  'items': [fileJson()],
  'material_revision': 4,
  'can_upload': upload,
  'can_remove': remove,
};

class DelayedFilesApi extends FakeApi {
  DelayedFilesApi()
    : super(
        routes: {
          'GET v2/activities/groups/7/materials/11/files': {
            'error': false,
            'data': pageJson(),
          },
        },
      );
  final gate = Completer<void>();
  @override
  Future<ResponseBody> fetch(
    RequestOptions options,
    Stream<Uint8List>? stream,
    Future<void>? cancelFuture,
  ) async {
    await gate.future;
    return super.fetch(options, stream, cancelFuture);
  }
}

class ByteClient extends ApiClient {
  ByteClient(FakeApi api)
    : super(
        authToken: () => 'synthetic',
        onUnauthorized: () {},
        createDio: () => Dio()..httpClientAdapter = api,
      );
  final reply = Completer<Uint8List>();
  final started = Completer<void>();
  @override
  Future<Uint8List> getBytes({
    required String url,
    int? schoolId,
    int? accountId,
    Duration? requestTimeout,
  }) {
    started.complete();
    return reply.future;
  }
}

class Files extends FakeActivityRepository
    implements ActivityFileProvider, ActivityFileRepository {
  @override
  int get groupId => 7;
  @override
  int get materialId => 11;
  bool uploadAllowed = true, removeAllowed = true;
  int uploads = 0, removals = 0;
  Object? uploadError, removalError;
  final intents = <MaterialFileIntent>[];
  final removalIntents = <ActivityMaterialAttachmentRemove>[];
  Future<Uint8List>? delayedBytes;
  Uint8List bytes = Uint8List.fromList(
    base64Decode(
      'iVBORw0KGgoAAAANSUhEUgAAAAEAAAABCAQAAAC1HAwCAAAAC0lEQVR42mP8/x8AAwMCAO+/hb8AAAAASUVORK5CYII=',
    ),
  );
  @override
  ActivityFileRepository files(int group, int material) => this;
  @override
  Future<ActivityMaterialAttachmentPage> listing() async {
    if (!isActive) throw ApiException('Denied', statusCode: 403);
    return ActivityMaterialAttachmentPage.fromJson(
      pageJson(upload: uploadAllowed, remove: removeAllowed),
    );
  }

  ActivityMaterialAttachmentChange change(bool removed) =>
      ActivityMaterialAttachmentChange.fromJson({
        ...fileJson(),
        'removed': removed,
        'revision': removed ? 2 : 1,
        'material_revision': 5,
      });
  @override
  Future<ActivityMaterialAttachmentChange> upload(
    MaterialFileIntent intent,
  ) async {
    uploads++;
    intents.add(intent);
    if (uploadError != null) throw uploadError!;
    return change(false);
  }

  @override
  Future<ActivityMaterialAttachmentChange> remove(
    String file,
    ActivityMaterialAttachmentRemove intent,
  ) async {
    removals++;
    removalIntents.add(intent);
    if (removalError != null) throw removalError!;
    return change(true);
  }

  @override
  Future<Uint8List> read(ActivityMaterialAttachment file) async =>
      delayedBytes ?? bytes;
}

Future<void> tap(WidgetTester tester, Finder finder) async {
  await tester.ensureVisible(finder);
  await tester.tap(finder);
  await tester.pumpAndSettle();
}

Future<void> open(
  WidgetTester tester,
  Files repo, {
  ProfileFilePicker? picker,
  double width = 390,
}) async {
  tester.view.physicalSize = Size(width, 900);
  tester.view.devicePixelRatio = 1;
  addTearDown(tester.view.resetPhysicalSize);
  addTearDown(tester.view.resetDevicePixelRatio);
  await tester.pumpWidget(
    MaterialApp(
      home: ActivityGroupsScreen(
        repository: repo,
        pickFile: picker ?? () async => null,
      ),
    ),
  );
  await tester.pumpAndSettle();
  await tap(tester, find.text('Hockey'));
  await tap(tester, find.text('Documents'));
}

Future<void> background(WidgetTester tester) async {
  tester.binding.handleAppLifecycleStateChanged(AppLifecycleState.inactive);
  await tester.pump();
  tester.binding.handleAppLifecycleStateChanged(AppLifecycleState.paused);
}

void main() {
  test(
    'file protocol pins account/school and refuses cross-purpose responses',
    () async {
      var active = true;
      final api = FakeApi(
        routes: {
          'GET v2/activities/groups/7/materials/11/files': {
            'error': false,
            'data': pageJson(),
          },
        },
      );
      final repo = ApiActivityFileRepository(
        ApiClient(
          authToken: () => 'synthetic',
          onUnauthorized: () {},
          createDio: () => Dio()..httpClientAdapter = api,
        ),
        schoolId: 1,
        accountId: 12,
        groupId: 7,
        materialId: 11,
        contextActive: () => active,
      );
      await repo.listing();
      expect(api.requests.single.headers['X-School-Id'], '1');
      expect(api.requests.single.headers['X-Account-Id'], '12');
      expect(api.requests.single.headers['Authorization'], 'Bearer synthetic');
      active = false;
      await expectLater(repo.listing(), throwsA(isA<ApiException>()));
      expect(api.requests.length, 1);
    },
  );
  test(
    'foreign nested IDs, withdrawn records and unsupported file types fail closed',
    () async {
      for (final altered in [
        {...fileJson(), 'activity_group_id': 8},
        {...fileJson(), 'material_id': 12},
        {...fileJson(), 'removed': true},
        {...fileJson(), 'mime': 'text/html'},
      ]) {
        final api = FakeApi(
          routes: {
            'GET v2/activities/groups/7/materials/11/files': {
              'error': false,
              'data': {
                ...pageJson(),
                'items': [altered],
              },
            },
          },
        );
        final repo = ApiActivityFileRepository(
          ApiClient(
            authToken: () => 'synthetic',
            onUnauthorized: () {},
            createDio: () => Dio()..httpClientAdapter = api,
          ),
          schoolId: 1,
          accountId: 12,
          groupId: 7,
          materialId: 11,
          contextActive: () => true,
        );
        await expectLater(repo.listing(), throwsA(isA<ApiException>()));
      }
    },
  );
  test(
    'erasing a selected upload while fresh authority is pending prevents POST',
    () async {
      final api = DelayedFilesApi();
      final repo = ApiActivityFileRepository(
        ApiClient(
          authToken: () => 'synthetic',
          onUnauthorized: () {},
          createDio: () => Dio()..httpClientAdapter = api,
        ),
        schoolId: 1,
        accountId: 12,
        groupId: 7,
        materialId: 11,
        contextActive: () => true,
      );
      final intent = MaterialFileIntent(
        operationId: fileId,
        materialRevision: 4,
        name: 'draft.pdf',
        bytes: Uint8List.fromList([1, 2, 3]),
      );
      final pending = expectLater(
        repo.upload(intent),
        throwsA(isA<ApiException>()),
      );
      intent.erase();
      api.gate.complete();
      await pending;
      expect(api.requests.every((request) => request.method == 'GET'), isTrue);
      expect(intent.bytes, everyElement(0));
    },
  );
  test(
    'late binary content is erased when the exact reader becomes inactive',
    () async {
      var active = true;
      final api = FakeApi(
        routes: {
          'GET v2/activities/groups/7/materials/11/files': {
            'error': false,
            'data': pageJson(),
          },
        },
      );
      final client = ByteClient(api);
      final repo = ApiActivityFileRepository(
        client,
        schoolId: 1,
        accountId: 12,
        groupId: 7,
        materialId: 11,
        contextActive: () => active,
      );
      final pending = expectLater(
        repo.read(ActivityMaterialAttachment.fromJson(fileJson())),
        throwsA(isA<ApiException>()),
      );
      await client.started.future;
      active = false;
      final bytes = Uint8List.fromList([1, 2, 3]);
      client.reply.complete(bytes);
      await pending;
      expect(bytes, everyElement(0));
    },
  );
  for (final width in [390.0, 820.0, 1280.0]) {
    testWidgets('family attachments preserve read-only controls at $width', (
      tester,
    ) async {
      final repo = Files()
        ..uploadAllowed = false
        ..removeAllowed = false;
      await open(tester, repo, width: width);
      expect(find.text('Practice.jpg'), findsOneWidget);
      expect(find.text('Upload document'), findsNothing);
      expect(find.text('Remove document'), findsNothing);
      expect(tester.takeException(), isNull);
    });
  }
  testWidgets(
    'unknown removal retries the exact operation and body erases on background',
    (tester) async {
      final repo = Files()
        ..removalError = ApiException('Unknown', isTransportFailure: true);
      await open(tester, repo);
      await tap(tester, find.text('Remove document'));
      await tap(tester, find.text('Remove document'));
      expect(repo.removals, 1);
      await tap(tester, find.text('Retry the same removal'));
      expect(repo.removals, 2);
      expect(
        repo.removalIntents[0].operationId,
        repo.removalIntents[1].operationId,
      );
      await background(tester);
      expect(find.text('Practice.jpg'), findsNothing);
      expect(find.text('Retry the same removal'), findsNothing);
      await tester.pumpWidget(const SizedBox());
    },
  );
  testWidgets(
    'picker unmounts group bodies and rechecks fresh capability before sending',
    (tester) async {
      final repo = Files();
      final selection = Completer<ProfileUpload?>();
      await open(tester, repo, picker: () => selection.future);
      await tester.tap(find.text('Upload document'));
      await tester.pump();
      await tester.pump();
      expect(find.text('Fixture note'), findsNothing);
      expect(find.text('Practice.jpg'), findsNothing);
      repo.uploadAllowed = false;
      final bytes = Uint8List.fromList([1, 2, 3]);
      selection.complete(ProfileUpload('draft.pdf', bytes));
      await tester.pumpAndSettle();
      expect(repo.uploads, 0);
      expect(bytes, everyElement(0));
    },
  );
  testWidgets(
    'unknown upload preserves exact UUID foreground and erases selected bytes on exit',
    (tester) async {
      final repo = Files()
        ..uploadError = ApiException('Unknown', isTransportFailure: true);
      final bytes = Uint8List.fromList([1, 2, 3]);
      await open(
        tester,
        repo,
        picker: () async => ProfileUpload('draft.pdf', bytes),
      );
      await tap(tester, find.text('Upload document'));
      expect(repo.uploads, 1);
      await tap(tester, find.text('Retry the same upload'));
      expect(repo.uploads, 2);
      expect(repo.intents[0].operationId, repo.intents[1].operationId);
      await background(tester);
      expect(bytes, everyElement(0));
      expect(repo.intents.first.erased, isTrue);
      await tester.pumpWidget(const SizedBox());
    },
  );
  testWidgets(
    'same route account replacement destroys old file reader and late bytes',
    (tester) async {
      final repo = Files(), reply = Completer<Uint8List>();
      repo.delayedBytes = reply.future;
      await open(tester, repo);
      await tester.tap(find.text('View document'));
      await tester.pump();
      final replacement = FakeActivityRepository()..accountId = 18;
      await tester.pumpWidget(
        MaterialApp(home: ActivityGroupsScreen(repository: replacement)),
      );
      final bytes = Uint8List.fromList([1, 2, 3]);
      reply.complete(bytes);
      await tester.pumpAndSettle();
      expect(bytes, everyElement(0));
      expect(find.text('Practice.jpg'), findsNothing);
      expect(find.byType(PrivateDocumentView), findsNothing);
      await tap(tester, find.text('Hockey'));
      expect(find.text('Documents'), findsNothing);
    },
  );
  testWidgets(
    'picker result for a replaced account is erased and cannot block the new reader',
    (tester) async {
      final repo = Files(), reply = Completer<ProfileUpload?>();
      await open(tester, repo, picker: () => reply.future);
      await tester.tap(find.text('Upload document'));
      await tester.pump();
      await tester.pump();
      final replacement = FakeActivityRepository()..accountId = 18;
      await tester.pumpWidget(
        MaterialApp(home: ActivityGroupsScreen(repository: replacement)),
      );
      await tester.pumpAndSettle();
      final bytes = Uint8List.fromList([1, 2, 3]);
      reply.complete(ProfileUpload('retired.pdf', bytes));
      await tester.pumpAndSettle();
      expect(repo.uploads, 0);
      expect(bytes, everyElement(0));
      expect(find.text('Finish choosing the private document.'), findsNothing);
      expect(find.text('Hockey'), findsOneWidget);
    },
  );
  testWidgets(
    'late authenticated bytes are zeroed after file reader is removed',
    (tester) async {
      final repo = Files(), reply = Completer<Uint8List>();
      repo.delayedBytes = reply.future;
      await open(tester, repo);
      await tester.tap(find.text('View document'));
      await tester.pump();
      await tester.pumpWidget(const SizedBox());
      final bytes = Uint8List.fromList([1, 2, 3]);
      reply.complete(bytes);
      await tester.pump();
      expect(bytes, everyElement(0));
    },
  );
}
