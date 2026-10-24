import 'dart:async';
import 'dart:convert';
import 'package:eschool_api/eschool_api.dart';
import 'package:eschool_core/eschool_core.dart';
import 'package:flutter_bloc/flutter_bloc.dart';
import 'package:uuid/uuid.dart';
import 'content_repository.dart';
import 'content_cache.dart';

String contentFailure(Object error) =>
    error is ApiException && error.isApiMessage
    ? error.errorMessage
    : 'Could not load learning content. Check your connection and access, then try again.';
bool contentRevoked(Object error) =>
    error is ApiException && [401, 403, 404].contains(error.statusCode);
bool contentOffline(Object error) =>
    error is ApiException && error.isTransportFailure;

class ContentLibraryRow {
  const ContentLibraryRow({
    required this.id,
    required this.versionId,
    required this.title,
    required this.summary,
    this.subjectName = '',
    this.expiresAt,
  });
  factory ContentLibraryRow.api(ContentLibraryItem row) => ContentLibraryRow(
    id: row.id,
    versionId: row.versionId,
    title: row.title,
    summary: row.summary,
    subjectName: row.subjectName,
  );
  factory ContentLibraryRow.saved(SavedContent row) => ContentLibraryRow(
    id: row.reader.contentItemId,
    versionId: row.reader.id,
    title: row.reader.title,
    summary: row.reader.summary,
    expiresAt: row.expiresAt,
  );
  final int id, versionId;
  final String title, summary, subjectName;
  final DateTime? expiresAt;
}

class ContentLibraryState {
  const ContentLibraryState({
    this.items = const [],
    this.page = 0,
    this.lastPage = 1,
    this.busy = false,
    this.offline = false,
    this.error,
  });
  final List<ContentLibraryRow> items;
  final int page, lastPage;
  final bool busy, offline;
  final String? error;
}

class ContentLibraryCubit extends Cubit<ContentLibraryState> {
  ContentLibraryCubit(this.repository, this.learner, {this.cache})
    : super(const ContentLibraryState());
  final AcademicContentRepository repository;
  final ContentReaderCache? cache;
  final int learner;
  int _generation = 0;
  Timer? _expiry;
  void clear() {
    _generation++;
    _expiry?.cancel();
    if (!isClosed) emit(const ContentLibraryState());
  }

  Future<void> load({bool more = false}) async {
    if (isClosed || more && (state.busy || state.page >= state.lastPage)) {
      return;
    }
    _expiry?.cancel();
    final generation = ++_generation, page = more ? state.page + 1 : 1;
    final previous = more ? state.items : <ContentLibraryRow>[];
    emit(
      ContentLibraryState(
        items: previous,
        page: more ? state.page : 0,
        lastPage: state.lastPage,
        busy: true,
      ),
    );
    try {
      if (!more && cache?.isActive == true) {
        try {
          await cache!.revalidate(learner);
        } catch (error) {
          if (error is ApiException) rethrow;
          cache!.close();
        }
      }
      final data = await repository.library(learner, page: page);
      if (isClosed || generation != _generation) return;
      if (!repository.isActive ||
          data.learnerId != learner ||
          data.page != page) {
        throw ApiException('content_scope_changed', statusCode: 403);
      }
      emit(
        ContentLibraryState(
          items: {
            for (final row in [
              ...previous,
              ...data.items.map(ContentLibraryRow.api),
            ])
              row.id: row,
          }.values.toList(),
          page: data.page,
          lastPage: data.lastPage,
        ),
      );
    } catch (error) {
      if (isClosed || generation != _generation) return;
      if (contentRevoked(error)) {
        try {
          await cache?.removeLearner(learner);
        } catch (_) {}
      }
      if (isClosed || generation != _generation) return;
      if (contentOffline(error) &&
          repository.isActive &&
          cache?.isActive == true) {
        try {
          final copies = await cache!.list(learner);
          if (isClosed || generation != _generation || !repository.isActive) {
            return;
          }
          emit(
            ContentLibraryState(
              items: copies.map(ContentLibraryRow.saved).toList(),
              page: 1,
              lastPage: 1,
              offline: true,
            ),
          );
          if (copies.isNotEmpty) {
            final first = copies
                .map((r) => r.expiresAt)
                .reduce((a, b) => a.isBefore(b) ? a : b);
            _expiry = Timer(first.difference(cache!.clock().toUtc()), () {
              if (!isClosed && generation == _generation) load();
            });
          }
          return;
        } catch (_) {}
      }
      if (!isClosed && generation == _generation) {
        emit(ContentLibraryState(error: contentFailure(error)));
      }
    }
  }

  @override
  Future<void> close() {
    _generation++;
    _expiry?.cancel();
    return super.close();
  }
}

class ContentReaderState {
  const ContentReaderState({
    this.reader,
    this.busy = false,
    this.saving = false,
    this.saved = false,
    this.offline = false,
    this.error,
    this.notice,
  });
  final ContentReader? reader;
  final bool busy, saving, saved, offline;
  final String? error, notice;
}

class ContentReaderCubit extends Cubit<ContentReaderState> {
  ContentReaderCubit(
    this.repository, {
    required this.learner,
    required this.version,
    this.cache,
  }) : super(const ContentReaderState());
  final AcademicContentRepository repository;
  final ContentReaderCache? cache;
  final int learner, version;
  int _generation = 0;
  Timer? _expiry;
  DateTime? _displayExpiry;
  DateTime now() => cache?.clock().toUtc() ?? DateTime.now().toUtc();
  void clear() {
    _generation++;
    _expiry?.cancel();
    if (!isClosed) emit(const ContentReaderState());
  }

  bool _current(int generation) =>
      !isClosed && generation == _generation && repository.isActive;
  void _verify(ContentReader data) {
    if (!repository.isActive ||
        data.id != version ||
        data.learnerId != learner ||
        data.schoolId != repository.schoolId ||
        data.readerId != repository.readerId) {
      throw ApiException('content_scope_changed', statusCode: 403);
    }
  }

  void _expire(DateTime instant, int generation) {
    if (cache == null) return;
    _displayExpiry = instant;
    _expiry?.cancel();
    _expiry = Timer(instant.difference(now()), () {
      if (_current(generation)) load();
    });
  }

  Future<void> load() async {
    if (isClosed) return;
    _expiry?.cancel();
    final generation = ++_generation, start = now();
    emit(const ContentReaderState(busy: true));
    try {
      final data = await repository.reader(learner, version);
      if (!_current(generation)) return;
      _verify(data);
      var saved = false;
      if (cache?.isActive == true) {
        try {
          saved = await cache!.read(learner, version) != null;
          if (!_current(generation)) return;
          if (saved) await cache!.save(data, requestedAt: start);
        } catch (_) {
          saved = false;
        }
      }
      if (!_current(generation)) return;
      emit(ContentReaderState(reader: data, saved: saved));
      final relative = start.add(data.offlineUntil.difference(data.serverTime));
      _expire(
        relative.isBefore(data.offlineUntil) ? relative : data.offlineUntil,
        generation,
      );
      try {
        await repository.read(learner, version);
      } catch (_) {}
    } catch (error) {
      if (isClosed || generation != _generation) return;
      if (contentRevoked(error)) {
        try {
          await cache?.remove(learner, version);
        } catch (_) {}
      }
      if (contentOffline(error) &&
          _current(generation) &&
          cache?.isActive == true) {
        try {
          final copy = await cache!.read(learner, version);
          if (!_current(generation)) return;
          if (copy != null) {
            _verify(copy.reader);
            emit(
              ContentReaderState(
                reader: copy.reader,
                saved: true,
                offline: true,
              ),
            );
            _expire(copy.expiresAt, generation);
            return;
          }
        } catch (_) {}
      }
      if (!isClosed && generation == _generation) {
        emit(ContentReaderState(error: contentFailure(error)));
      }
    }
  }

  Future<void> saveOffline() async {
    if (state.reader == null ||
        state.busy ||
        state.saving ||
        state.offline ||
        cache?.isActive != true) {
      return;
    }
    final previous = state, generation = ++_generation, start = now();
    final previousExpiry = _displayExpiry ?? previous.reader!.offlineUntil;
    emit(
      ContentReaderState(
        reader: previous.reader,
        saved: previous.saved,
        saving: true,
      ),
    );
    _expire(previousExpiry, generation);
    try {
      final fresh = await repository.reader(learner, version);
      if (!_current(generation)) return;
      _verify(fresh);
      await cache!.save(fresh, requestedAt: start);
      if (!_current(generation)) return;
      emit(
        ContentReaderState(
          reader: fresh,
          saved: true,
          notice:
              'Saved privately for offline reading. Copies expire within one day.',
        ),
      );
      final copy = await cache!.read(learner, version);
      if (_current(generation) && copy != null) {
        _expire(copy.expiresAt, generation);
      }
    } catch (error) {
      if (!_current(generation)) return;
      if (contentRevoked(error)) {
        try {
          await cache!.remove(learner, version);
        } catch (_) {}
        if (_current(generation)) {
          emit(ContentReaderState(error: contentFailure(error)));
        }
      } else {
        emit(
          ContentReaderState(
            reader: previous.reader,
            saved: previous.saved,
            notice:
                'Could not save a fresh offline copy. Check your connection and device storage.',
          ),
        );
        _expire(previousExpiry, generation);
      }
    }
  }

  Future<void> removeOffline() async {
    if (state.saving || cache?.isActive != true) return;
    final previous = state, generation = _generation;
    try {
      await cache!.remove(learner, version);
      if (!_current(generation)) return;
      if (previous.offline) {
        _expiry?.cancel();
        emit(
          const ContentReaderState(
            error: 'Saved copy removed. Connect to open this material again.',
          ),
        );
      } else {
        emit(
          ContentReaderState(
            reader: previous.reader,
            notice: 'Saved copy removed.',
          ),
        );
        if (previous.reader != null) {
          _expire(_displayExpiry ?? previous.reader!.offlineUntil, generation);
        }
      }
    } catch (_) {
      if (_current(generation)) {
        emit(
          ContentReaderState(
            reader: previous.reader,
            offline: previous.offline,
            saved: previous.saved,
            notice: 'Could not remove the saved copy. Try again.',
          ),
        );
      }
    }
  }

  @override
  Future<void> close() {
    _generation++;
    _expiry?.cancel();
    return super.close();
  }
}

class ContentEditorState {
  const ContentEditorState({
    this.item,
    this.version,
    this.busy = false,
    this.validated = false,
    this.uncertain = false,
    this.conflict = false,
    this.saved = 0,
    this.preview,
    this.error,
  });
  final ContentItem? item;
  final ContentVersion? version;
  final ContentPreview? preview;
  final bool busy, validated, uncertain, conflict;
  final int saved;
  final String? error;
  bool get writable => validated && (item?.canWrite ?? true);
}

class ContentEditorCubit extends Cubit<ContentEditorState> {
  ContentEditorCubit(this.repository, {this.itemId})
    : super(const ContentEditorState());
  final AcademicContentRepository repository;
  int? itemId;
  int _generation = 0;
  Future<ContentReceipt> Function()? _pending;
  ContentEditorState _view({
    bool busy = false,
    bool? validated,
    bool uncertain = false,
    bool conflict = false,
    String? error,
    ContentPreview? preview,
    int? saved,
  }) => ContentEditorState(
    item: state.item,
    version: state.version,
    busy: busy,
    validated: validated ?? state.validated,
    uncertain: uncertain,
    conflict: conflict,
    error: error,
    preview: preview,
    saved: saved ?? state.saved,
  );
  void hide() {
    _generation++;
    if (!isClosed) emit(_view(validated: false, uncertain: _pending != null));
  }

  Future<void> load({
    bool discardPending = false,
    bool keepConflict = false,
  }) async {
    if (discardPending) _pending = null;
    final generation = ++_generation;
    emit(_view(busy: true, validated: false, uncertain: _pending != null));
    try {
      if (!repository.isActive) {
        throw ApiException('content_scope_changed', statusCode: 403);
      }
      final item = itemId == null ? null : await repository.item(itemId!);
      if (isClosed || generation != _generation) return;
      if (!repository.isActive) {
        throw ApiException('content_scope_changed', statusCode: 403);
      }
      if (item != null && item.id != itemId) {
        throw ApiException(ApiErrorCodes.defaultErrorMessageKey);
      }
      emit(
        ContentEditorState(
          item: item,
          version: item?.draft ?? item?.published,
          validated: true,
          uncertain: _pending != null,
          conflict: keepConflict,
          saved: state.saved,
        ),
      );
    } catch (error) {
      if (!isClosed && generation == _generation) {
        emit(
          ContentEditorState(error: contentFailure(error), saved: state.saved),
        );
      }
    }
  }

  void acceptComparison() {
    if (!isClosed) emit(_view());
  }

  Future<void> _submit(Future<ContentReceipt> Function() command) async {
    if (!state.writable ||
        state.busy ||
        state.conflict ||
        (_pending != null && command != _pending)) {
      return;
    }
    var receiptKnown = false;
    final generation = ++_generation;
    _pending = command;
    emit(_view(busy: true));
    try {
      final receipt = await command();
      if (isClosed || generation != _generation) return;
      if (!repository.isActive) {
        throw ApiException('content_scope_changed', statusCode: 403);
      }
      itemId = receipt.id;
      receiptKnown = true;
      _pending = null;
      final item = await repository.item(receipt.id);
      if (isClosed || generation != _generation) return;
      if (!repository.isActive || item.id != receipt.id) {
        throw ApiException('content_scope_changed', statusCode: 403);
      }
      emit(
        ContentEditorState(
          item: item,
          version: item.draft ?? item.published,
          validated: true,
          saved: state.saved + 1,
        ),
      );
    } catch (error) {
      if (isClosed || generation != _generation) return;
      final status = error is ApiException ? error.statusCode : null;
      if (contentRevoked(error)) {
        _pending = null;
        emit(
          ContentEditorState(error: contentFailure(error), saved: state.saved),
        );
        return;
      }
      if (receiptKnown) {
        emit(_view(validated: false, error: contentFailure(error)));
        return;
      }
      if (status != null && status >= 400 && status < 500) _pending = null;
      if (status == 409) {
        await load(discardPending: true, keepConflict: true);
        if (!isClosed && state.validated) {
          emit(_view(conflict: true, error: contentFailure(error)));
        }
      } else {
        emit(_view(uncertain: _pending != null, error: contentFailure(error)));
      }
    }
  }

  Future<void> retryPending() async {
    final pending = _pending;
    if (pending != null) await _submit(pending);
  }

  ContentSelection _selection(ContentSelection selection) =>
      ContentSelection.fromJson(
        Map<String, dynamic>.from(
          jsonDecode(jsonEncode(selection.toJson())) as Map,
        ),
      );
  Future<void> create(ContentSelection selection, ContentUpload source) {
    final frozen = _selection(selection), operation = const Uuid().v4();
    return _submit(() => repository.create(frozen, source, operation));
  }

  ContentRevisionWrite _revisions() => ContentRevisionWrite(
    operationId: const Uuid().v4(),
    itemRevision: state.item!.revision,
    versionRevision: state.version!.revision,
  );
  Future<void> replace(ContentSelection selection, ContentUpload source) {
    if (state.item == null || state.version == null) return Future.value();
    final frozen = _selection(selection),
        revisions = _revisions(),
        id = state.item!.id;
    return _submit(() => repository.replace(id, frozen, source, revisions));
  }

  Future<void> save(ContentSelection selection, String markdown) {
    if (state.item == null || state.version == null) return Future.value();
    final r = _revisions(), id = state.version!.id;
    final input = ContentDraftWrite(
      operationId: r.operationId,
      itemRevision: r.itemRevision,
      versionRevision: r.versionRevision,
      selection: _selection(selection),
      markdown: markdown,
    );
    return _submit(() => repository.save(id, input));
  }

  Future<void> retryConversion() {
    if (state.version == null || state.item == null) return Future.value();
    final r = _revisions(), id = state.version!.id;
    return _submit(() => repository.retry(id, r));
  }

  Future<void> preview() async {
    if (state.busy ||
        _pending != null ||
        state.conflict ||
        state.version == null) {
      return;
    }
    final generation = ++_generation;
    emit(_view(busy: true));
    try {
      final preview = await repository.preview(state.version!.id);
      if (isClosed || generation != _generation) return;
      if (!repository.isActive ||
          preview.id != state.version!.id ||
          preview.contentItemId != state.item?.id) {
        throw ApiException('content_scope_changed', statusCode: 403);
      }
      emit(_view(preview: preview));
    } catch (error) {
      if (!isClosed && generation == _generation) {
        emit(
          contentRevoked(error)
              ? ContentEditorState(
                  error: contentFailure(error),
                  saved: state.saved,
                )
              : _view(error: contentFailure(error)),
        );
      }
    }
  }

  Future<void> publish({required bool reviewed, String? reason}) {
    final p = state.preview;
    if (p == null || !reviewed) return Future.value();
    final input = ContentPublishWrite(
      operationId: const Uuid().v4(),
      itemRevision: p.itemRevision,
      versionRevision: p.versionRevision,
      fingerprint: p.fingerprint,
      reviewed: true,
      reason: reason,
    );
    return _submit(() => repository.publish(p.id, input));
  }

  Future<void> archive(String reason) {
    final item = state.item;
    if (item == null) return Future.value();
    final input = ContentArchiveWrite(
      operationId: const Uuid().v4(),
      itemRevision: item.revision,
      reason: reason,
    );
    return _submit(() => repository.archive(item.id, input));
  }

  @override
  Future<void> close() {
    _generation++;
    _pending = null;
    return super.close();
  }
}
