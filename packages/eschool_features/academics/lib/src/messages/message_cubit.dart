import 'package:eschool_api/eschool_api.dart';
import 'package:eschool_core/eschool_core.dart';
import 'package:flutter_bloc/flutter_bloc.dart';
import 'package:uuid/uuid.dart';
import 'message_repository.dart';

String messageFailure(Object error) =>
    error is ApiException && error.isApiMessage
    ? error.errorMessage
    : 'Could not load school messages. Check your connection and access, then try again.';
bool messageRevoked(Object error) =>
    error is ApiException && [401, 403, 404].contains(error.statusCode);

class MessageListState {
  const MessageListState({
    this.configuration,
    this.items = const [],
    this.page = 0,
    this.lastPage = 1,
    this.busy = false,
    this.error,
  });
  final CommunicationConfiguration? configuration;
  final List<CommunicationSummary> items;
  final int page, lastPage;
  final bool busy;
  final String? error;
}

class MessageListCubit extends Cubit<MessageListState> {
  MessageListCubit(this.repository) : super(const MessageListState());
  final SchoolMessageRepository repository;
  bool drafts = false;
  int _generation = 0;
  void clear() {
    _generation++;
    if (!isClosed) emit(const MessageListState());
  }

  Future<void> load({bool? prepared, bool more = false}) async {
    if (isClosed || more && (state.busy || state.page >= state.lastPage)) {
      return;
    }
    if (prepared != null) drafts = prepared;
    final generation = ++_generation, page = more ? state.page + 1 : 1;
    final previous = more ? state.items : <CommunicationSummary>[];
    final oldConfig = state.configuration;
    emit(
      MessageListState(
        configuration: more ? oldConfig : null,
        items: previous,
        page: more ? state.page : 0,
        lastPage: state.lastPage,
        busy: true,
      ),
    );
    try {
      final config = more ? oldConfig! : await repository.configuration();
      final rows = await repository.messages(
        drafts: drafts && config.staffAvailable,
        page: page,
      );
      if (isClosed || generation != _generation) return;
      if (!repository.isActive ||
          config.readerId != repository.readerId ||
          config.schoolId != repository.schoolId) {
        throw ApiException('message_scope_changed', statusCode: 403);
      }
      emit(
        MessageListState(
          configuration: config,
          items: {
            for (final row in [...previous, ...rows.items]) row.id: row,
          }.values.toList(),
          page: rows.page,
          lastPage: rows.lastPage,
        ),
      );
    } catch (error) {
      if (!isClosed && generation == _generation) {
        emit(MessageListState(error: messageFailure(error)));
      }
    }
  }

  @override
  Future<void> close() {
    _generation++;
    return super.close();
  }
}

class MessageReaderState {
  const MessageReaderState({
    this.reader,
    this.busy = false,
    this.read = false,
    this.error,
    this.acknowledgementError,
  });
  final CommunicationMessageReader? reader;
  final bool busy, read;
  final String? error, acknowledgementError;
}

class MessageReaderCubit extends Cubit<MessageReaderState> {
  MessageReaderCubit(this.repository, this.id)
    : super(const MessageReaderState());
  final SchoolMessageRepository repository;
  final int id;
  int _generation = 0;
  void clear() {
    _generation++;
    if (!isClosed) emit(const MessageReaderState());
  }

  Future<void> load() async {
    if (isClosed) return;
    final generation = ++_generation;
    emit(const MessageReaderState(busy: true));
    try {
      final reader = await repository.reader(id);
      if (isClosed || generation != _generation) return;
      if (!repository.isActive ||
          reader.id != id ||
          reader.schoolId != repository.schoolId ||
          reader.readerId != repository.readerId) {
        throw ApiException('message_scope_changed', statusCode: 403);
      }
      emit(MessageReaderState(reader: reader, read: reader.readAt != null));
      if (reader.readAt == null) await acknowledge();
    } catch (error) {
      if (!isClosed && generation == _generation) {
        emit(MessageReaderState(error: messageFailure(error)));
      }
    }
  }

  Future<void> acknowledge() async {
    final reader = state.reader;
    if (reader == null || state.read || !repository.isActive) return;
    final generation = _generation;
    try {
      await repository.read(id);
      if (!isClosed && generation == _generation && repository.isActive) {
        emit(MessageReaderState(reader: reader, read: true));
      }
    } catch (error) {
      if (isClosed || generation != _generation) return;
      emit(
        messageRevoked(error) || !repository.isActive
            ? MessageReaderState(error: messageFailure(error))
            : MessageReaderState(
                reader: reader,
                acknowledgementError:
                    'Could not save the read acknowledgement. Retry when connected.',
              ),
      );
    }
  }

  @override
  Future<void> close() {
    _generation++;
    return super.close();
  }
}

class MessageEditorState {
  const MessageEditorState({
    this.configuration,
    this.editor,
    this.preview,
    this.busy = false,
    this.validated = false,
    this.uncertain = false,
    this.conflict = false,
    this.dirty = false,
    this.formVersion = 0,
    this.error,
  });
  final CommunicationConfiguration? configuration;
  final CommunicationMessageEditor? editor;
  final CommunicationMessagePreview? preview;
  final bool busy, validated, uncertain, conflict, dirty;
  final int formVersion;
  final String? error;
}

class MessageEditorCubit extends Cubit<MessageEditorState> {
  MessageEditorCubit(this.repository, {this.messageId})
    : super(const MessageEditorState());
  final SchoolMessageRepository repository;
  int? messageId;
  bool get hasPendingWrite => _pending != null;
  int _generation = 0;
  Future<CommunicationMessageReceipt> Function()? _pending;
  MessageEditorState _view({
    bool busy = false,
    bool? validated,
    bool? dirty,
    bool uncertain = false,
    bool conflict = false,
    String? error,
    CommunicationMessagePreview? preview,
  }) => MessageEditorState(
    configuration: state.configuration,
    editor: state.editor,
    preview: preview,
    busy: busy,
    validated: validated ?? state.validated,
    dirty: dirty ?? state.dirty,
    uncertain: uncertain,
    conflict: conflict,
    formVersion: state.formVersion,
    error: error,
  );
  void changed() {
    if (!isClosed && !state.busy && _pending == null) {
      emit(_view(dirty: true, conflict: state.conflict));
    }
  }

  void hide() {
    _generation++;
    if (!isClosed) {
      emit(
        _view(
          validated: false,
          uncertain: _pending != null,
          conflict: state.conflict,
        ),
      );
    }
  }

  Future<void> load({bool keepLocal = false}) async {
    if (isClosed) return;
    final generation = ++_generation, version = state.formVersion;
    emit(_view(busy: true, validated: false, uncertain: _pending != null));
    try {
      if (!repository.isActive) {
        throw ApiException('message_scope_changed', statusCode: 403);
      }
      final editor = messageId == null
          ? null
          : await repository.editor(messageId!);
      final config = await repository.configuration(
        year:
            editor?.audience.sessionYearId ??
            state.configuration?.sessionYearId,
      );
      if (isClosed || generation != _generation) return;
      if (!repository.isActive || !config.staffAvailable) {
        throw ApiException('message_scope_changed', statusCode: 403);
      }
      emit(
        MessageEditorState(
          configuration: config,
          editor: editor,
          validated: true,
          uncertain: _pending != null,
          conflict: keepLocal && _pending == null,
          dirty: keepLocal,
          formVersion: keepLocal ? version : version + 1,
        ),
      );
    } catch (error) {
      if (!isClosed && generation == _generation) {
        final revoked = messageRevoked(error);
        if (revoked) _pending = null;
        emit(
          MessageEditorState(
            error: messageFailure(error),
            formVersion: version,
            uncertain: _pending != null,
            dirty: keepLocal && !revoked,
            conflict: keepLocal && !revoked && _pending == null,
          ),
        );
      }
    }
  }

  void acceptComparison() {
    if (!isClosed) emit(_view(dirty: true));
  }

  Future<void> _submit(
    Future<CommunicationMessageReceipt> Function() command,
  ) async {
    if (isClosed ||
        !state.validated ||
        state.busy ||
        state.conflict ||
        (_pending != null && command != _pending)) {
      return;
    }
    final generation = ++_generation;
    var receiptKnown = false;
    _pending = command;
    emit(_view(busy: true));
    try {
      final result = await command();
      if (isClosed || generation != _generation) return;
      if (!repository.isActive ||
          result.id <= 0 ||
          (messageId != null && result.id != messageId)) {
        throw ApiException('message_scope_changed', statusCode: 403);
      }
      messageId = result.id;
      _pending = null;
      receiptKnown = true;
      await load();
    } catch (error) {
      if (isClosed || generation != _generation) return;
      if (messageRevoked(error)) {
        _pending = null;
        emit(
          MessageEditorState(
            error: messageFailure(error),
            formVersion: state.formVersion,
          ),
        );
        return;
      }
      final status = error is ApiException ? error.statusCode : null;
      if (receiptKnown || status != null && status >= 400 && status < 500) {
        _pending = null;
      }
      if (status == 409) {
        await load(keepLocal: true);
      } else {
        emit(
          _view(
            uncertain: _pending != null,
            validated: !receiptKnown,
            error: messageFailure(error),
          ),
        );
      }
    }
  }

  Future<void> retryPending() async {
    final pending = _pending;
    if (pending != null) await _submit(pending);
  }

  Future<void> save({
    required String title,
    required String markdown,
    required CommunicationAudienceSelection audience,
    required Set<String> channels,
    String? scheduledLocal,
  }) {
    final input = CommunicationMessageWrite(
      operationId: const Uuid().v4(),
      revision: state.editor?.revision ?? 0,
      title: title,
      markdown: markdown,
      audience: CommunicationAudienceSelection.fromJson(audience.toJson()),
      channels: Set<String>.unmodifiable(channels),
      scheduledLocal: scheduledLocal?.isEmpty == true ? null : scheduledLocal,
    );
    final id = messageId;
    return _submit(() => repository.save(id, input));
  }

  Future<void> preview() async {
    final editor = state.editor;
    if (isClosed ||
        !state.validated ||
        state.busy ||
        state.dirty ||
        state.conflict ||
        _pending != null ||
        editor == null) {
      return;
    }
    final generation = ++_generation;
    emit(_view(busy: true));
    try {
      final p = await repository.preview(editor.id);
      if (isClosed || generation != _generation) return;
      if (!repository.isActive || p.id != editor.id) {
        throw ApiException('message_scope_changed', statusCode: 403);
      }
      if (p.revision != editor.revision) {
        await load(keepLocal: true);
        return;
      }
      emit(_view(preview: p));
    } catch (error) {
      if (!isClosed && generation == _generation) {
        emit(
          messageRevoked(error)
              ? MessageEditorState(
                  error: messageFailure(error),
                  formVersion: state.formVersion,
                )
              : _view(error: messageFailure(error)),
        );
      }
    }
  }

  Future<void> submitPreview() {
    final p = state.preview;
    if (p == null || state.dirty) return Future.value();
    final input = CommunicationSubmitWrite(
      operationId: const Uuid().v4(),
      revision: p.revision,
      fingerprint: p.fingerprint,
    );
    return _submit(() => repository.submit(p.id, input));
  }

  Future<void> decide(bool approved, String reason) {
    final p = state.preview;
    if (p == null || state.dirty) return Future.value();
    final input = CommunicationDecisionWrite.fromJson({
      'operation_id': const Uuid().v4(),
      'revision': p.revision,
      'fingerprint': p.fingerprint,
      'decision': approved ? 'approve' : 'reject',
      'reason': reason,
    });
    return _submit(() => repository.decide(p.id, input));
  }

  Future<void> withdraw(String reason) {
    final editor = state.editor;
    if (editor == null || state.dirty) return Future.value();
    final input = CommunicationWithdrawWrite(
      operationId: const Uuid().v4(),
      revision: editor.revision,
      reason: reason,
    );
    return _submit(() => repository.withdraw(editor.id, input));
  }

  @override
  Future<void> close() {
    _generation++;
    _pending = null;
    return super.close();
  }
}
