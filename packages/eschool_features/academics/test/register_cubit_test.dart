import 'dart:async';
import 'package:eschool_api/eschool_api.dart';
import 'package:eschool_core/eschool_core.dart';
import 'package:flutter_test/flutter_test.dart';
import 'package:eschool_academics/src/registers/register_cubit.dart';
import 'package:eschool_academics/src/registers/register_repository.dart';
import 'package:eschool_academics/src/registers/register_store.dart';
import 'package:eschool_academics/src/testing/register_fixtures.dart';

class FakeRegisterRepository implements RegisterRepository {
  @override
  int schoolId = 1;
  AcademicRegisterMatrixData server = AcademicRegisterMatrixData.fromJson(
    registerMatrixFixture(),
  );
  Object? syncError, readError;
  Future<AcademicRegisterMatrixData> Function()? read;
  final writes = <AcademicRegisterSyncWrite>[];
  final applied = <String, AcademicRegisterSyncData>{};
  @override
  Future<AcademicRegisterMatrixData> matrix(int register) async {
    if (readError != null) throw readError!;
    return read == null ? server : await read!();
  }

  @override
  Future<AcademicRegisterSyncData> sync(
    int register,
    AcademicRegisterSyncWrite input,
  ) async {
    writes.add(input);
    if (syncError != null) throw syncError!;
    if (applied[input.operationId] case final previous?) return previous;
    if (input.baseRevision != server.revision) {
      throw ApiException(
        'Register changed',
        isApiMessage: true,
        statusCode: 409,
      );
    }
    final changes = {for (final row in input.rows) row.learnerId: row};
    final revisions = <AcademicRegisterEntryRevision>[];
    final rows = <Map<String, dynamic>>[];
    for (final row in server.learners) {
      final change = changes[row.learnerId];
      if (change == null) {
        rows.add(row.toJson());
        continue;
      }
      if (change.expectedRevision != row.revision) {
        throw ApiException('Entry changed', statusCode: 409);
      }
      final revision = row.state == change.state
          ? row.revision
          : row.revision + 1;
      revisions.add(
        AcademicRegisterEntryRevision(
          learnerId: row.learnerId,
          revision: revision,
        ),
      );
      rows.add({
        ...row.toJson(),
        'state': change.state,
        'revision': revision,
        'source': change.source_,
      });
    }
    server = AcademicRegisterMatrixData.fromJson({
      ...server.toJson(),
      'revision': server.revision + 1,
      'learners': rows,
    });
    final result = AcademicRegisterSyncData(
      registerId: register,
      revision: server.revision,
      entries: revisions,
    );
    applied[input.operationId] = result;
    return result;
  }

  @override
  Future<AcademicRegisterChoicesData> choices({
    String? date,
    int? year,
  }) async => AcademicRegisterChoicesData.fromJson(registerChoicesFixture());
  @override
  Future<AcademicRegisterMatrixData> open(
    AcademicRegisterTargetWrite input,
  ) async => server;
  @override
  Future<AcademicAttendanceTodayData> today(int learner) async =>
      AcademicAttendanceTodayData.fromJson({
        'learner_id': learner,
        'on_date': '2026-02-10',
        'entries': [],
      });
  @override
  Future<AcademicRegisterQrData> qr(int learner) async =>
      AcademicRegisterQrData(qrCode: 'NEAR1.synthetic-$learner.signature');
  @override
  Future<AcademicRegisterQrData> rotate(int learner) async =>
      AcademicRegisterQrData(qrCode: 'NEAR1.replaced-$learner.signature');
}

class FailingRegisterStore extends MemoryRegisterStore {
  bool fail = false, failRemove = false;
  @override
  Future<void> write(String key, Map<String, dynamic> value) async {
    if (fail) throw StateError('Synthetic storage failure');
    return super.write(key, value);
  }

  @override
  Future<void> remove(String key) async {
    if (failRemove) throw StateError('Synthetic purge failure');
    return super.remove(key);
  }
}

RegisterCubit cubit(FakeRegisterRepository repository, RegisterStore store) {
  var sequence = 0;
  return RegisterCubit(
    repository,
    store,
    4,
    operationId: () => 'operation-${++sequence}',
  );
}

void main() {
  test(
    'offline queue survives reopening and replays its exact UUID and revisions',
    () async {
      final repository = FakeRegisterRepository()
        ..syncError = StateError('Offline');
      final store = MemoryRegisterStore();
      final first = cubit(repository, store);
      await first.load();
      first.set(11, 'present');
      await first.save();
      expect(first.state.queue.single.operationId, 'operation-1');
      expect(first.state.queue.single.rows.single.expectedRevision, 0);
      expect(first.state.offline, isTrue);
      await first.close();
      repository.syncError = null;
      final reopened = cubit(repository, store);
      addTearDown(reopened.close);
      await reopened.load();
      expect(reopened.state.queue, isEmpty);
      expect(reopened.state.rows.first.state, 'present');
      expect(repository.applied.length, 1);
      expect(repository.writes.map((row) => row.operationId).toSet(), {
        'operation-1',
      });
    },
  );

  test(
    'two offline operations preserve order and predicted entry revisions',
    () async {
      final repository = FakeRegisterRepository()
        ..syncError = StateError('Offline');
      final subject = cubit(repository, MemoryRegisterStore());
      addTearDown(subject.close);
      await subject.load();
      subject.set(11, 'present');
      await subject.save();
      subject.reason('Learner arrived later');
      subject.set(11, 'late');
      await subject.save();
      expect(subject.state.queue.map((row) => row.baseRevision), [0, 1]);
      expect(
        subject.state.queue.map((row) => row.rows.single.expectedRevision),
        [0, 1],
      );
      repository.syncError = null;
      await subject.flush();
      expect(repository.applied.keys, ['operation-1', 'operation-2']);
      expect(repository.server.learners.first.state, 'late');
      expect(subject.state.matrix!.revision, 2);
    },
  );

  test(
    'conflicts keep the original queue until explicit review creates a new draft',
    () async {
      final repository = FakeRegisterRepository()
        ..syncError = ApiException(
          'Competing register edit',
          isApiMessage: true,
          statusCode: 409,
        );
      final subject = cubit(repository, MemoryRegisterStore());
      addTearDown(subject.close);
      await subject.load();
      repository.server = AcademicRegisterMatrixData.fromJson(
        registerMatrixFixture(revision: 3, state: 'absent', entryRevision: 1),
      );
      subject.set(11, 'present');
      await subject.save();
      expect(subject.state.conflict, isTrue);
      expect(subject.state.queue.single.baseRevision, 0);
      expect(repository.writes.length, 1);
      await subject.flush();
      expect(repository.writes.length, 1);
      await subject.useReviewedChanges({11});
      expect(subject.state.queue, isEmpty);
      expect(subject.state.draft[11], 'present');
      expect(repository.writes.length, 1);
      subject.reason('Compared with the current register');
      repository.syncError = null;
      await subject.save();
      expect(repository.writes.last.operationId, 'operation-2');
      expect(repository.writes.last.baseRevision, 3);
      expect(repository.writes.last.rows.single.expectedRevision, 1);
      expect(subject.state.queue, isEmpty);
    },
  );

  test(
    'QR duplicates do not add writes and foreign QR codes never identify a peer',
    () async {
      final repository = FakeRegisterRepository();
      final subject = cubit(repository, MemoryRegisterStore());
      addTearDown(subject.close);
      await subject.load();
      subject.scan('NEAR1.synthetic-11.signature');
      subject.scan('NEAR1.synthetic-11.signature');
      expect(subject.state.notice, contains('already marked present'));
      expect(subject.state.draft.length, 1);
      subject.scan('NEAR1.other-school.signature');
      expect(subject.state.error, contains('not on this dated roster'));
      await subject.save();
      expect(repository.writes.single.rows.single.source_, 'qr');
      expect(
        repository.writes.single.rows.single.qrToken,
        'NEAR1.synthetic-11.signature',
      );
    },
  );

  test(
    'past-day queues remain for a grade head while revoked reads clear all roster data',
    () async {
      final repository = FakeRegisterRepository()
        ..syncError = ApiException('Not allowed', statusCode: 403);
      final store = MemoryRegisterStore();
      final subject = cubit(repository, store);
      addTearDown(subject.close);
      await subject.load();
      repository.server = AcademicRegisterMatrixData.fromJson(
        registerMatrixFixture(canWrite: false),
      );
      subject.set(11, 'absent');
      await subject.save();
      expect(subject.state.blocked, isTrue);
      expect(subject.state.queue.length, 1);
      expect(await store.read(subject.cacheKey), isNotNull);
      repository.readError = ApiException(
        'Revoked allocation',
        statusCode: 403,
      );
      await subject.reviewConflict();
      expect(subject.state.matrix, isNull);
      expect(subject.state.rows, isEmpty);
      expect(await store.read(subject.cacheKey), isNull);
    },
  );

  test(
    'storage failure retains the draft and never claims it was queued or sends it',
    () async {
      final repository = FakeRegisterRepository();
      final store = FailingRegisterStore();
      final subject = cubit(repository, store);
      addTearDown(subject.close);
      await subject.load();
      store.fail = true;
      subject.set(11, 'present');
      await subject.save();
      expect(subject.state.draft[11], 'present');
      expect(subject.state.queue, isEmpty);
      expect(repository.writes, isEmpty);
      expect(subject.state.error, contains('Could not save offline'));
    },
  );

  test(
    'permission loss clears the screen even when cache deletion fails',
    () async {
      final repository = FakeRegisterRepository();
      final store = FailingRegisterStore();
      final subject = cubit(repository, store);
      addTearDown(subject.close);
      await subject.load();
      repository.syncError = ApiException('Revoked', statusCode: 403);
      repository.readError = ApiException('Revoked', statusCode: 403);
      store.failRemove = true;
      subject.set(11, 'present');
      await subject.save();
      expect(subject.state.matrix, isNull);
      expect(subject.state.rows, isEmpty);
    },
  );

  test(
    'late loads cannot write a private roster after its context closes',
    () async {
      final repository = FakeRegisterRepository();
      final pending = Completer<AcademicRegisterMatrixData>();
      repository.read = () => pending.future;
      final store = MemoryRegisterStore();
      final subject = cubit(repository, store);
      final loading = subject.load();
      await Future<void>.delayed(Duration.zero);
      await subject.close();
      pending.complete(repository.server);
      await loading;
      expect(subject.state.matrix, isNull);
      expect(await store.read(subject.cacheKey), isNull);
    },
  );

  test(
    'offline rosters are isolated by school and never borrowed for another school',
    () async {
      final store = MemoryRegisterStore();
      final first = cubit(FakeRegisterRepository(), store);
      await first.load();
      await first.close();
      final repository = FakeRegisterRepository()
        ..schoolId = 2
        ..readError = StateError('Offline');
      final other = cubit(repository, store);
      addTearDown(other.close);
      await other.load();
      expect(other.state.matrix, isNull);
      expect(other.state.rows, isEmpty);
    },
  );
}
