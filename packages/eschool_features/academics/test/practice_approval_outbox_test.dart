import 'dart:io';
import 'package:eschool_academics/eschool_academics.dart';
import 'package:eschool_core/eschool_core.dart';
import 'package:flutter_secure_storage/flutter_secure_storage.dart';
import 'package:flutter_test/flutter_test.dart';
import 'package:hive/hive.dart';
import 'practice_fixture.dart';

PracticeIntent approval() => PracticeIntent.approve(
  7,
  PracticeApproveInput(
    operationId: '11111111-1111-4111-8111-111111111111',
    expectedRevision: 0,
    topicId: 22,
    contentVersionId: 23,
    estimatedSeconds: 120,
    active: true,
    definition: practiceSet().items.single.definition,
  ),
);
void main() {
  TestWidgetsFlutterBinding.ensureInitialized();
  test(
    'durable approval restores exact payload after disposal and refuses an altered operation',
    () async {
      final repo = FakePracticeRepository()..staff = true,
          store = MemoryContentStore();
      final first = PracticeApprovalOutbox(repo, store: () async => store);
      await first.stage(approval());
      first.close();
      final next = PracticeApprovalOutbox(repo, store: () async => store);
      final restored = (await next.restore(7)).intent!;
      expect(restored.savedApproval(), approval().savedApproval());
      final changed = PracticeIntent.approve(
        7,
        PracticeApproveInput.fromJson({
          ...approval().body,
          'operation_id': '22222222-2222-4222-8222-222222222222',
        }),
      );
      await expectLater(next.stage(changed), throwsStateError);
      await next.complete(restored);
      expect((await next.restore(7)).intent, isNull);
      next.close();
    },
  );
  test(
    'unresolved approval is not silently forgotten after a long background interval',
    () async {
      final repo = FakePracticeRepository()..staff = true,
          store = MemoryContentStore();
      var time = DateTime.utc(2026, 10, 25);
      final first = PracticeApprovalOutbox(
        repo,
        store: () async => store,
        clock: () => time,
      );
      await first.stage(approval());
      first.close();
      time = time.add(const Duration(days: 10));
      final restored = PracticeApprovalOutbox(
        repo,
        store: () async => store,
        clock: () => time,
      );
      expect((await restored.restore(7)).intent!.body, approval().body);
      restored.close();
    },
  );
  test(
    'revoked original group is reauthorized before opening any saved payload',
    () async {
      final repo = FakePracticeRepository()..staff = true,
          store = MemoryContentStore();
      var opens = 0;
      final first = PracticeApprovalOutbox(repo, store: () async => store);
      await first.stage(approval());
      first.close();
      repo.staff = false;
      final next = PracticeApprovalOutbox(
        repo,
        store: () async {
          opens++;
          return store;
        },
      );
      await expectLater(next.restore(7), throwsA(isA<ApiException>()));
      expect(opens, 0);
      expect((await store.keys()).length, 1);
      next.close();
    },
  );
  test(
    'approval intent survives real encrypted Hive restart with no plaintext draft on disk',
    () async {
      final directory = await Directory.systemTemp.createTemp(
        'academic-practice-approval-',
      );
      Hive.init(directory.path);
      FlutterSecureStorage.setMockInitialValues({});
      final repo = FakePracticeRepository()..staff = true;
      late PracticeApprovalOutbox first;
      first = PracticeApprovalOutbox(
        repo,
        store: () => SecurePracticeApprovalStore.open(
          12,
          1,
          active: () => first.isActive,
        ),
      );
      try {
        await first.stage(approval());
        first.close();
        await Hive.close();
        final bytes = await File(
          '${directory.path}/academic_practice_approval_v1.hive',
        ).readAsBytes();
        expect(
          String.fromCharCodes(bytes),
          isNot(contains('One half as a decimal?')),
        );
        late PracticeApprovalOutbox second;
        second = PracticeApprovalOutbox(
          repo,
          store: () => SecurePracticeApprovalStore.open(
            12,
            1,
            active: () => second.isActive,
          ),
        );
        expect((await second.restore(7)).intent!.body, approval().body);
        second.close();
      } finally {
        await SecurePracticeApprovalStore.purge();
        await Hive.close();
        await directory.delete(recursive: true);
      }
    },
  );
}
