import 'dart:async';
import 'dart:typed_data';
import 'package:eschool_academics/eschool_academics.dart';
import 'package:eschool_academics/testing.dart';
import 'package:eschool_core/eschool_core.dart';
import 'package:flutter_test/flutter_test.dart';

class FakeContentRepository implements AcademicContentRepository {
  @override
  int schoolId = 1;
  @override
  int readerId = 12;
  @override
  bool isActive = true;
  bool writable = true;
  int revision = 2, reads = 0, creates = 0, publishes = 0;
  Object? error;
  final operations = <String>[];
  final drafts = <ContentDraftWrite>[];
  Future<ContentLibrary> Function(int, int)? list;
  Future<ContentReader> Function()? fetchReader;
  Future<ContentReceipt> Function(ContentDraftWrite)? saveDraft;
  Future<ContentReceipt> Function()? createSource;
  @override
  Future<ContentConfiguration> configuration({int? year}) async =>
      ContentConfiguration.fromJson(contentConfigurationFixture());
  @override
  Future<ContentList> items(int year, {int page = 1}) async =>
      ContentList.fromJson(contentItemsFixture());
  @override
  Future<ContentItem> item(int id, {int page = 1}) async {
    if (error != null) throw error!;
    return ContentItem.fromJson(
      contentItemFixture(writable: writable, revision: revision),
    );
  }

  @override
  Future<ContentVersion> version(int id) async =>
      ContentVersion.fromJson(contentVersionFixture());
  @override
  Future<ContentLibrary> library(int learner, {int page = 1}) async {
    if (error != null) throw error!;
    return list != null
        ? await list!(learner, page)
        : ContentLibrary.fromJson(
            contentLibraryFixture(learner: learner, page: page, lastPage: 2),
          );
  }

  @override
  Future<ContentReader> reader(int learner, int version) async {
    if (error != null) throw error!;
    return fetchReader != null
        ? await fetchReader!()
        : ContentReader.fromJson(contentReaderFixture(learner: learner));
  }

  @override
  Future<void> read(int learner, int version) async {
    reads++;
  }

  ContentReceipt receipt() => ContentReceipt(
    id: 10,
    versionId: 11,
    itemRevision: revision,
    versionRevision: revision,
  );
  @override
  Future<ContentReceipt> create(
    ContentSelection selection,
    ContentUpload source,
    String operation,
  ) async {
    creates++;
    operations.add(operation);
    return createSource != null ? await createSource!() : receipt();
  }

  @override
  Future<ContentReceipt> save(int version, ContentDraftWrite input) async {
    operations.add(input.operationId);
    drafts.add(input);
    return saveDraft != null ? await saveDraft!(input) : receipt();
  }

  @override
  Future<ContentPreview> preview(int version) async =>
      ContentPreview.fromJson(contentPreviewFixture());
  @override
  Future<ContentReceipt> publish(int version, ContentPublishWrite input) async {
    publishes++;
    return receipt();
  }

  @override
  dynamic noSuchMethod(Invocation invocation) =>
      throw UnimplementedError('${invocation.memberName}');
}

void main() {
  final selection = ContentSelection.fromJson(contentSelectionFixture());
  test(
    'library paginates without duplicates and clears on revoked access',
    () async {
      final repo = FakeContentRepository();
      final library = ContentLibraryCubit(repo, 11);
      addTearDown(library.close);
      await library.load();
      await library.load(more: true);
      expect(library.state.items.length, 1);
      expect(library.state.page, 2);
      repo.error = ApiException('Forbidden', statusCode: 403);
      await library.load();
      expect(library.state.items, isEmpty);
    },
  );
  test(
    'late library and reader answers cannot restore cleared state',
    () async {
      final repo = FakeContentRepository();
      final pending = Completer<ContentLibrary>();
      repo.list = (_, _) => pending.future;
      final library = ContentLibraryCubit(repo, 11);
      addTearDown(library.close);
      final loading = library.load();
      library.clear();
      pending.complete(ContentLibrary.fromJson(contentLibraryFixture()));
      await loading;
      expect(library.state.items, isEmpty);
      final response = Completer<ContentReader>();
      repo.fetchReader = () => response.future;
      final reader = ContentReaderCubit(repo, learner: 11, version: 11);
      addTearDown(reader.close);
      final opening = reader.load();
      reader.clear();
      response.complete(ContentReader.fromJson(contentReaderFixture()));
      await opening;
      expect(reader.state.reader, isNull);
      expect(repo.reads, 0);
    },
  );
  test(
    'reader rejects another child and records read only after valid render data',
    () async {
      final repo = FakeContentRepository(),
          readerRepo = FakeContentRepository();
      readerRepo.fetchReader = () async =>
          ContentReader.fromJson(contentReaderFixture(learner: 12));
      final wrong = ContentReaderCubit(readerRepo, learner: 11, version: 11);
      addTearDown(wrong.close);
      await wrong.load();
      expect(wrong.state.reader, isNull);
      expect(readerRepo.reads, 0);
      final reader = ContentReaderCubit(repo, learner: 11, version: 11);
      addTearDown(reader.close);
      await reader.load();
      expect(reader.state.reader!.title, 'Fractions');
      expect(repo.reads, 1);
      repo.error = ApiException('Forbidden', statusCode: 403);
      await reader.load();
      expect(reader.state.reader, isNull);
    },
  );
  test(
    'uncertain upload reuses frozen source and operation instead of duplicating',
    () async {
      final repo = FakeContentRepository();
      repo.createSource = () async {
        if (repo.creates == 1) throw TimeoutException('connection lost');
        return repo.receipt();
      };
      final editor = ContentEditorCubit(repo);
      addTearDown(editor.close);
      await editor.load();
      final bytes = Uint8List.fromList([1, 2, 3]);
      final upload = ContentUpload('lesson.md', bytes);
      bytes[0] = 9;
      expect(upload.bytes.first, 1);
      await editor.create(selection, upload);
      expect(editor.state.uncertain, isTrue);
      await editor.create(selection, upload);
      expect(repo.creates, 1);
      await editor.retryPending();
      expect(repo.creates, 2);
      expect(repo.operations.toSet().length, 1);
      expect(editor.state.uncertain, isFalse);
      expect(editor.itemId, 10);
    },
  );
  test(
    'conflict reload preserves server revisions until explicit comparison',
    () async {
      final repo = FakeContentRepository();
      repo.saveDraft = (_) async {
        repo.revision = 3;
        throw ApiException('Changed', statusCode: 409);
      };
      final editor = ContentEditorCubit(repo, itemId: 10);
      addTearDown(editor.close);
      await editor.load();
      await editor.save(selection, 'My draft');
      expect(editor.state.conflict, isTrue);
      expect(editor.state.item!.revision, 3);
      await editor.save(selection, 'Another');
      expect(repo.drafts.length, 1);
      editor.acceptComparison();
      repo.saveDraft = (_) async => repo.receipt();
      await editor.save(selection, 'My draft');
      expect(repo.drafts.last.itemRevision, 3);
      expect(repo.drafts.last.markdown, 'My draft');
    },
  );
  test('revocation during conflict refresh cannot re-enable writes', () async {
    final repo = FakeContentRepository();
    repo.saveDraft = (_) async {
      repo.error = ApiException('Forbidden', statusCode: 403);
      throw ApiException('Changed', statusCode: 409);
    };
    final editor = ContentEditorCubit(repo, itemId: 10);
    addTearDown(editor.close);
    await editor.load();
    await editor.save(selection, 'My draft');
    editor.acceptComparison();
    expect(editor.state.writable, isFalse);
    expect(editor.state.item, isNull);
  });
  test(
    'view-only allocation and missing explicit human review never publish',
    () async {
      final repo = FakeContentRepository()..writable = false;
      final editor = ContentEditorCubit(repo, itemId: 10);
      addTearDown(editor.close);
      await editor.load();
      await editor.preview();
      await editor.publish(reviewed: true);
      expect(repo.publishes, 0);
      repo.writable = true;
      await editor.load();
      await editor.preview();
      await editor.publish(reviewed: false);
      expect(repo.publishes, 0);
      await editor.publish(reviewed: true);
      expect(repo.publishes, 1);
    },
  );
  test(
    'background hide and changed identity keep editor unvalidated',
    () async {
      final repo = FakeContentRepository();
      final current = ContentEditorCubit(repo, itemId: 10);
      addTearDown(current.close);
      await current.load();
      current.hide();
      expect(current.state.writable, isFalse);
      repo.isActive = false;
      await current.load();
      expect(current.state.item, isNull);
    },
  );
}
