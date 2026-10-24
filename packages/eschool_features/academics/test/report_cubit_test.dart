import 'dart:async';
import 'dart:convert';
import 'dart:typed_data';
import 'package:eschool_academics/eschool_academics.dart';
import 'package:eschool_academics/testing.dart';
import 'package:eschool_core/eschool_core.dart';
import 'package:flutter_test/flutter_test.dart';

class FakeReportRepository implements ReportRepository {
  @override
  int schoolId = 1;
  Object? error;
  int reads = 0;
  final familyRequests = <int>[];
  final keys = <String>[];
  final writes = <AcademicReportCommentWrite>[];
  Future<AcademicReportFamilyData> Function(int learner, int page)? list;
  Future<Uint8List> Function()? pdf;
  Future<AcademicReportCommentData> Function(AcademicReportCommentWrite input)?
  save;
  @override
  Future<AcademicReportFamilyData> family(int learner, {int page = 1}) async {
    familyRequests.add(learner);
    if (error != null) throw error!;
    return list != null
        ? await list!(learner, page)
        : AcademicReportFamilyData.fromJson(
            reportFamilyFixture(
              AppConfig.apiV2Url,
              page: page,
              id: 10 - page,
              learner: learner,
            ),
          );
  }

  @override
  Future<Uint8List> familyPdf(int card, int learner) async {
    if (error != null) throw error!;
    return pdf != null ? await pdf!() : base64Decode(reportSmokePdfBase64);
  }

  @override
  Future<Uint8List> staffPdf(int batch, int card) => familyPdf(card, 11);
  @override
  Future<void> read(int card) async {
    reads++;
  }

  @override
  Future<AcademicReportCommentData> comment(
    int card,
    AcademicReportCommentWrite input,
    String key,
  ) async {
    writes.add(input);
    keys.add(key);
    if (error != null) throw error!;
    return save != null
        ? await save!(input)
        : AcademicReportCommentData(
            id: 1,
            revision: input.revision + 1,
            batchRevision: input.batchRevision + 1,
            fingerprint: 'a' * 64,
            text: input.text ?? '',
          );
  }

  @override
  dynamic noSuchMethod(Invocation invocation) =>
      throw UnimplementedError('${invocation.memberName}');
}

void main() {
  test('family pages append retained releases without duplicates', () async {
    final repo = FakeReportRepository();
    final cubit = FamilyReportsCubit(repo, 11);
    addTearDown(cubit.close);
    await cubit.load();
    expect(cubit.state.reports.map((r) => r.id), [9]);
    await cubit.load(more: true);
    expect(cubit.state.reports.map((r) => r.id), [9, 8]);
    await cubit.load(more: true);
    expect(repo.familyRequests, [11, 11]);
  });
  test(
    'revoked guardian access clears prior history even during paging',
    () async {
      final repo = FakeReportRepository();
      final cubit = FamilyReportsCubit(repo, 11);
      addTearDown(cubit.close);
      await cubit.load();
      repo.error = ApiException('Forbidden', statusCode: 403);
      await cubit.load(more: true);
      expect(cubit.state.reports, isEmpty);
      expect(cubit.state.error, isNotNull);
    },
  );
  test(
    'transport failure keeps the previous history page available for retry',
    () async {
      final repo = FakeReportRepository();
      final cubit = FamilyReportsCubit(repo, 11);
      addTearDown(cubit.close);
      await cubit.load();
      repo.error = StateError('offline');
      await cubit.load(more: true);
      expect(cubit.state.reports, hasLength(1));
      expect(cubit.state.page, 1);
      repo.error = null;
      await cubit.load(more: true);
      expect(cubit.state.reports, hasLength(2));
    },
  );
  test('a later refresh wins over a late previous response', () async {
    final old = Completer<AcademicReportFamilyData>();
    final repo = FakeReportRepository();
    var calls = 0;
    repo.list = (learner, page) async => calls++ == 0
        ? await old.future
        : AcademicReportFamilyData.fromJson(
            reportFamilyFixture(AppConfig.apiV2Url, id: 10),
          );
    final cubit = FamilyReportsCubit(repo, 11);
    addTearDown(cubit.close);
    final pending = cubit.load();
    await cubit.load();
    old.complete(
      AcademicReportFamilyData.fromJson(
        reportFamilyFixture(AppConfig.apiV2Url),
      ),
    );
    await pending;
    expect(cubit.state.reports.single.id, 10);
  });
  test('wrong-owner data never becomes family state', () async {
    final repo = FakeReportRepository()
      ..list = (learner, page) async => AcademicReportFamilyData.fromJson(
        reportFamilyFixture(AppConfig.apiV2Url, learner: 12),
      );
    final cubit = FamilyReportsCubit(repo, 11);
    addTearDown(cubit.close);
    await cubit.load();
    expect(cubit.state.reports, isEmpty);
    expect(cubit.state.error, isNotNull);
  });
  test('closing family history ignores pending responses', () async {
    final pending = Completer<AcademicReportFamilyData>();
    final repo = FakeReportRepository()..list = (_, _) => pending.future;
    final cubit = FamilyReportsCubit(repo, 11);
    final load = cubit.load();
    await cubit.close();
    pending.complete(
      AcademicReportFamilyData.fromJson(
        reportFamilyFixture(AppConfig.apiV2Url),
      ),
    );
    await load;
    expect(cubit.isClosed, isTrue);
  });
  test(
    'PDF refresh immediately clears bytes and a refusal never restores them',
    () async {
      final repo = FakeReportRepository();
      final cubit = ReportPdfCubit(repo, card: 9, learner: 11);
      addTearDown(cubit.close);
      await cubit.load();
      expect(cubit.state.bytes, isNotNull);
      expect(repo.reads, 1);
      repo.error = ApiException('Forbidden', statusCode: 403);
      final reload = cubit.load();
      expect(cubit.state.bytes, isNull);
      await reload;
      expect(cubit.state.bytes, isNull);
      expect(cubit.state.error, isNotNull);
    },
  );
  test(
    'clearing/closing a PDF suppresses late bytes and read acknowledgements',
    () async {
      final pending = Completer<Uint8List>();
      final repo = FakeReportRepository()..pdf = () => pending.future;
      final cubit = ReportPdfCubit(repo, card: 9, learner: 11);
      final load = cubit.load();
      cubit.clear();
      await cubit.close();
      pending.complete(base64Decode(reportSmokePdfBase64));
      await load;
      expect(cubit.state.bytes, isNull);
      expect(repo.reads, 0);
    },
  );
  test(
    'comments preserve the draft and UUID after a failed network save',
    () async {
      final repo = FakeReportRepository()..error = StateError('offline');
      final cubit = ReportCommentCubit(
        repo,
        AcademicReportCommentRow.fromJson(reportCommentFixture()),
      );
      addTearDown(cubit.close);
      cubit.change('My useful comment');
      await cubit.save();
      expect(cubit.state.dirty, isTrue);
      expect(cubit.state.text, 'My useful comment');
      repo.error = null;
      await cubit.save();
      expect(repo.keys[0], repo.keys[1]);
      expect(repo.writes.first.toJson(), repo.writes.last.toJson());
      expect(cubit.state.dirty, isFalse);
      expect(cubit.state.revision, 1);
    },
  );
  test(
    'changed requests get new UUIDs and conflicts require explicit comparison',
    () async {
      final repo = FakeReportRepository()
        ..error = ApiException('Changed', statusCode: 409);
      final cubit = ReportCommentCubit(
        repo,
        AcademicReportCommentRow.fromJson(reportCommentFixture()),
      );
      addTearDown(cubit.close);
      cubit.change('My draft');
      await cubit.save();
      expect(cubit.state.conflict, isTrue);
      await cubit.save();
      expect(repo.writes, hasLength(1));
      cubit.reviewed(
        AcademicReportCommentRow.fromJson(
          reportCommentFixture(
            text: 'Someone else',
            revision: 2,
            batchRevision: 4,
          ),
        ),
        keepDraft: true,
      );
      expect(cubit.state.text, 'My draft');
      expect(cubit.state.saved, 'Someone else');
      repo.error = null;
      await cubit.save();
      expect(repo.writes.last.revision, 2);
      expect(repo.writes.last.batchRevision, 4);
      expect(repo.keys[1], isNot(repo.keys[0]));
    },
  );
  test('unchanged and in-flight comments cannot send another write', () async {
    final pending = Completer<AcademicReportCommentData>();
    final repo = FakeReportRepository()..save = (_) => pending.future;
    final cubit = ReportCommentCubit(
      repo,
      AcademicReportCommentRow.fromJson(reportCommentFixture()),
    );
    addTearDown(cubit.close);
    await cubit.save();
    expect(repo.writes, isEmpty);
    cubit.change('Draft');
    final save = cubit.save();
    await cubit.save();
    cubit.change('Ignored during save');
    expect(cubit.state.text, 'Draft');
    pending.complete(
      AcademicReportCommentData(
        id: 1,
        revision: 1,
        batchRevision: 2,
        fingerprint: 'a' * 64,
        text: 'Draft',
      ),
    );
    await save;
    expect(repo.writes, hasLength(1));
  });
}
