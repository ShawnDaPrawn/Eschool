import 'dart:async';
import 'package:eschool_api/eschool_api.dart';
import 'package:eschool_core/eschool_core.dart';
import 'package:flutter_bloc/flutter_bloc.dart';
import 'package:uuid/uuid.dart';
import 'register_repository.dart';
import 'register_store.dart';

class RegisterRowView {
  const RegisterRowView(this.learner, this.state, this.revision);
  final AcademicRegisterLearner learner;
  final String state;
  final int revision;
}

class RegisterState {
  const RegisterState({
    this.matrix,
    this.queue = const [],
    this.draft = const {},
    this.sources = const {},
    this.reason = '',
    this.loading = true,
    this.busy = false,
    this.offline = false,
    this.conflict = false,
    this.blocked = false,
    this.error,
    this.notice,
    this.review,
  });
  final AcademicRegisterMatrixData? matrix, review;
  final List<AcademicRegisterSyncWrite> queue;
  final Map<int, String> draft, sources;
  final String reason;
  final bool loading, busy, offline, conflict, blocked;
  final String? error, notice;
  bool get canEdit =>
      matrix?.canWrite == true && !busy && !blocked && !conflict;
  List<RegisterRowView> get committedRows {
    final visible = review ?? matrix;
    if (visible == null) return [];
    return visible.learners.map((learner) {
      var value = learner.state, revision = learner.revision;
      if (review == null) {
        for (final operation in queue) {
          for (final row in operation.rows.where(
            (row) => row.learnerId == learner.learnerId,
          )) {
            if (value != row.state) {
              value = row.state;
              revision++;
            }
          }
        }
      }
      return RegisterRowView(learner, value, revision);
    }).toList();
  }

  List<RegisterRowView> get rows => committedRows
      .map(
        (row) => RegisterRowView(
          row.learner,
          draft[row.learner.learnerId] ?? row.state,
          row.revision,
        ),
      )
      .toList();
  RegisterState copy({
    AcademicRegisterMatrixData? matrix,
    List<AcademicRegisterSyncWrite>? queue,
    Map<int, String>? draft,
    Map<int, String>? sources,
    String? reason,
    bool? loading,
    bool? busy,
    bool? offline,
    bool? conflict,
    bool? blocked,
    String? error,
    String? notice,
    AcademicRegisterMatrixData? review,
    bool clearReview = false,
  }) => RegisterState(
    matrix: matrix ?? this.matrix,
    queue: queue ?? this.queue,
    draft: draft ?? this.draft,
    sources: sources ?? this.sources,
    reason: reason ?? this.reason,
    loading: loading ?? this.loading,
    busy: busy ?? this.busy,
    offline: offline ?? this.offline,
    conflict: conflict ?? this.conflict,
    blocked: blocked ?? this.blocked,
    error: error,
    notice: notice,
    review: clearReview ? null : review ?? this.review,
  );
}

/// A saved queue is immutable until acknowledged or explicitly reviewed/discarded.
/// All server operations retain their original register/row revisions and UUID.
class RegisterCubit extends Cubit<RegisterState> {
  RegisterCubit(
    this.repository,
    this.store,
    this.register, {
    String Function()? operationId,
    this.requestTimeout = const Duration(seconds: 12),
  }) : _operationId = operationId ?? const Uuid().v4,
       super(const RegisterState());
  final RegisterRepository repository;
  final RegisterStore store;
  final int register;
  final String Function() _operationId;
  final Duration requestTimeout;
  String get cacheKey => '${repository.schoolId}:register:$register';
  int _generation = 0;
  Timer? _retry;
  Future<void> _writes = Future<void>.value();
  String _message(Object error) => error is ApiException && error.isApiMessage
      ? error.errorMessage
      : 'Could not open the register. Try again.';
  Map<String, dynamic> _bundle(RegisterState value) => {
    'matrix': value.matrix!.toJson(),
    'queue': value.queue.map((row) => row.toJson()).toList(),
    'draft': value.draft.map((key, value) => MapEntry('$key', value)),
    'sources': value.sources.map((key, value) => MapEntry('$key', value)),
    'reason': value.reason,
  };
  Future<void> _persist(RegisterState value) {
    if (value.matrix == null) return Future<void>.value();
    final data = _bundle(value);
    final complete = Completer<void>();
    _writes = _writes.then((_) async {
      try {
        await store.write(cacheKey, data);
        complete.complete();
      } catch (error, stack) {
        complete.completeError(error, stack);
      }
    });
    return complete.future;
  }

  Future<void> _forget(String message, int generation) async {
    if (!isClosed && generation == _generation) {
      emit(RegisterState(loading: false, error: message));
    }
    await _writes;
    try {
      await store.remove(cacheKey);
    } catch (_) {
      /* An account purge also revokes the encrypted box. */
    }
  }

  Future<void> load({AcademicRegisterMatrixData? initial}) async {
    final generation = ++_generation;
    _retry?.cancel();
    emit(const RegisterState());
    RegisterState? cached;
    try {
      final value = await store.read(cacheKey);
      if (value != null) {
        cached = RegisterState(
          matrix: AcademicRegisterMatrixData.fromJson(
            Map<String, dynamic>.from(value['matrix'] as Map),
          ),
          queue: (value['queue'] as List? ?? [])
              .map(
                (row) => AcademicRegisterSyncWrite.fromJson(
                  Map<String, dynamic>.from(row as Map),
                ),
              )
              .toList(),
          draft: (value['draft'] as Map? ?? {}).map(
            (key, value) => MapEntry(int.parse('$key'), '$value'),
          ),
          sources: (value['sources'] as Map? ?? {}).map(
            (key, value) => MapEntry(int.parse('$key'), '$value'),
          ),
          reason: value['reason'] as String? ?? '',
          loading: false,
          offline: true,
        );
      }
      if (isClosed || generation != _generation) return;
      if (cached != null &&
          (cached.queue.isNotEmpty || cached.draft.isNotEmpty)) {
        try {
          final current = await repository
              .matrix(register)
              .timeout(requestTimeout);
          if (isClosed || generation != _generation) return;
          if (!current.canWrite) {
            emit(
              cached.copy(
                blocked: true,
                review: current,
                offline: false,
                error: 'Saved changes need a grade head to review this day.',
              ),
            );
          } else if (current.rosterHash != cached.matrix!.rosterHash ||
              (cached.queue.isEmpty &&
                  current.revision != cached.matrix!.revision)) {
            emit(
              cached.copy(
                conflict: true,
                review: current,
                offline: false,
                error: 'The register changed. Review your saved work.',
              ),
            );
          } else {
            emit(
              cached.copy(
                offline: false,
                notice:
                    'Saved work restored. Sync checks current permissions and revisions.',
              ),
            );
            await flush();
          }
        } catch (error) {
          if (error is ApiException &&
              [401, 403, 404].contains(error.statusCode)) {
            rethrow;
          }
          if (!isClosed && generation == _generation) {
            emit(
              cached.copy(
                offline: true,
                error: 'Offline copy. Your saved work is retained.',
              ),
            );
          }
        }
      } else {
        try {
          final matrix =
              initial ??
              await repository.matrix(register).timeout(requestTimeout);
          if (isClosed || generation != _generation) return;
          final next = RegisterState(matrix: matrix, loading: false);
          await _persist(next);
          if (!isClosed && generation == _generation) emit(next);
        } catch (error) {
          if (error is ApiException &&
              [401, 403, 404].contains(error.statusCode)) {
            rethrow;
          }
          if (cached == null) rethrow;
          if (!isClosed && generation == _generation) {
            emit(
              cached.copy(
                error:
                    'Offline copy. Saved changes will be checked when the school reconnects.',
              ),
            );
          }
        }
      }
      if (!isClosed && generation == _generation) {
        _retry = Timer.periodic(const Duration(seconds: 30), (_) {
          if (!state.busy &&
              !state.conflict &&
              !state.blocked &&
              state.queue.isNotEmpty) {
            unawaited(flush());
          }
        });
      }
    } catch (error) {
      if (error is ApiException && [401, 403, 404].contains(error.statusCode)) {
        await _forget(_message(error), generation);
        return;
      }
      if (!isClosed && generation == _generation) {
        emit(RegisterState(loading: false, error: _message(error)));
      }
    }
  }

  void set(int learner, String value, {String source = 'tap'}) {
    if (!state.canEdit ||
        ![
          'present',
          'absent',
          'late',
          'excused',
          'first_half',
          'second_half',
          'holiday',
        ].contains(value)) {
      return;
    }
    if (state.matrix?.timetableLessonId != null &&
        ['first_half', 'second_half', 'holiday'].contains(value)) {
      return;
    }
    final matches = state.committedRows.where(
      (row) => row.learner.learnerId == learner,
    );
    if (matches.isEmpty) return;
    final original = matches.single;
    final draft = {...state.draft}, sources = {...state.sources};
    if (original.state == value) {
      draft.remove(learner);
      sources.remove(learner);
    } else {
      draft[learner] = value;
      sources[learner] = source;
    }
    final next = state.copy(
      draft: Map.unmodifiable(draft),
      sources: Map.unmodifiable(sources),
    );
    emit(next);
    unawaited(
      _persist(next).catchError((Object error) {
        if (!isClosed) {
          emit(
            state.copy(
              error:
                  'Could not retain this draft offline. Keep this screen open and retry.',
            ),
          );
        }
      }),
    );
  }

  void remainingPresent() {
    if (!state.canEdit) return;
    for (final row in state.rows.where((row) => row.state == 'unmarked')) {
      set(row.learner.learnerId, 'present');
    }
  }

  void reason(String value) {
    if (!state.canEdit) return;
    final next = state.copy(reason: value);
    emit(next);
    unawaited(
      _persist(next).catchError((Object error) {
        if (!isClosed) {
          emit(state.copy(error: 'Could not retain this draft offline.'));
        }
      }),
    );
  }

  void scan(String code) {
    if (!state.canEdit) return;
    final found = state.rows.where((row) => row.learner.qrCode == code.trim());
    if (found.isEmpty) {
      emit(
        state.copy(
          error:
              'This QR code is not on this dated roster. Reload or select the learner.',
        ),
      );
      return;
    }
    final row = found.single;
    if (row.state == 'present') {
      emit(
        state.copy(notice: '${row.learner.name} is already marked present.'),
      );
      return;
    }
    set(row.learner.learnerId, 'present', source: 'qr');
    emit(state.copy(notice: '${row.learner.name} marked present.'));
  }

  Future<void> save() async {
    if (!state.canEdit || state.draft.isEmpty) return;
    final generation = _generation;
    final rows = <AcademicRegisterRowWrite>[];
    for (final row in state.committedRows) {
      final desired = state.draft[row.learner.learnerId];
      if (desired == null) continue;
      if ((row.revision > 0 || row.learner.source_ == 'legacy') &&
          state.reason.trim().length < 3) {
        emit(state.copy(error: 'Give a reason for the attendance correction.'));
        return;
      }
      final source = state.sources[row.learner.learnerId] ?? 'tap';
      rows.add(
        AcademicRegisterRowWrite(
          learnerId: row.learner.learnerId,
          state: desired,
          expectedRevision: row.revision,
          source_: source == 'qr'
              ? 'qr'
              : state.offline
              ? 'offline'
              : 'tap',
          qrToken: source == 'qr' ? row.learner.qrCode : null,
        ),
      );
    }
    final operation = AcademicRegisterSyncWrite(
      operationId: _operationId(),
      baseRevision: state.matrix!.revision + state.queue.length,
      rosterHash: state.matrix!.rosterHash,
      reason: state.reason.trim().isEmpty ? null : state.reason.trim(),
      rows: rows,
    );
    final next = state.copy(
      queue: List.unmodifiable([...state.queue, operation]),
      draft: const {},
      sources: const {},
      busy: true,
      notice: 'Saving your queue on this device…',
    );
    emit(state.copy(busy: true));
    try {
      await _persist(next);
      if (isClosed || generation != _generation) return;
      emit(
        next.copy(busy: false, notice: 'Saved on this device. Sync pending.'),
      );
      await flush();
    } catch (error) {
      if (!isClosed && generation == _generation) {
        emit(
          state.copy(
            busy: false,
            error:
                'Could not save offline. Your draft is still here; keep this screen open.',
          ),
        );
      }
    }
  }

  AcademicRegisterMatrixData _ack(
    AcademicRegisterMatrixData matrix,
    AcademicRegisterSyncWrite operation,
    AcademicRegisterSyncData result,
  ) {
    final revisions = {
      for (final row in result.entries) row.learnerId: row.revision,
    };
    final writes = {for (final row in operation.rows) row.learnerId: row};
    return AcademicRegisterMatrixData.fromJson({
      ...matrix.toJson(),
      'revision': result.revision,
      'learners': [
        for (final learner in matrix.learners)
          {
            ...learner.toJson(),
            if (writes[learner.learnerId] case final write?) ...{
              'state': write.state,
              'revision': revisions[learner.learnerId]!,
              'source': write.source_,
            },
          },
      ],
    });
  }

  Future<void> flush() async {
    if (isClosed ||
        state.busy ||
        state.conflict ||
        state.blocked ||
        state.queue.isEmpty) {
      return;
    }
    final generation = _generation;
    emit(state.copy(busy: true));
    try {
      while (!isClosed && generation == _generation && state.queue.isNotEmpty) {
        final operation = state.queue.first;
        final result = await repository
            .sync(register, operation)
            .timeout(requestTimeout);
        if (isClosed || generation != _generation) return;
        final next = state.copy(
          matrix: _ack(state.matrix!, operation, result),
          queue: List.unmodifiable(state.queue.skip(1)),
          offline: false,
          notice: 'Register synced',
        );
        // Retain a completed operation if acknowledgement persistence fails: a retry is exact and harmless.
        await _persist(next);
        if (!isClosed && generation == _generation) emit(next);
      }
      if (!isClosed && generation == _generation) {
        final current = await repository
            .matrix(register)
            .timeout(requestTimeout);
        if (isClosed || generation != _generation) return;
        if (state.draft.isNotEmpty &&
            (current.revision != state.matrix!.revision ||
                current.rosterHash != state.matrix!.rosterHash)) {
          emit(
            state.copy(
              busy: false,
              conflict: true,
              review: current,
              error:
                  'Saved changes synced. Review the current register before saving the remaining draft.',
            ),
          );
        } else {
          final next = state.copy(
            matrix: current,
            busy: false,
            offline: false,
            notice: 'Register synced',
          );
          await _persist(next);
          if (!isClosed && generation == _generation) emit(next);
        }
      }
    } catch (error) {
      if (isClosed || generation != _generation) return;
      if (error is ApiException && error.statusCode == 401) {
        await _forget('Sign in again to open the register.', generation);
      } else if (error is ApiException &&
          [403, 404].contains(error.statusCode)) {
        if (state.queue.isEmpty) {
          await _forget('Access changed. Open the register again.', generation);
          return;
        }
        try {
          final current = await repository
              .matrix(register)
              .timeout(requestTimeout);
          if (!isClosed && generation == _generation) {
            emit(
              state.copy(
                busy: false,
                blocked: true,
                review: current,
                error:
                    'These saved changes need a grade head to review this day. The queue is retained.',
              ),
            );
          }
        } catch (_) {
          await _forget(
            'Access changed. The cached register was removed.',
            generation,
          );
        }
      } else if (error is ApiException &&
          [409, 422].contains(error.statusCode)) {
        emit(state.copy(busy: false, conflict: true, error: _message(error)));
        await reviewConflict();
      } else {
        emit(
          state.copy(
            busy: false,
            offline: true,
            error: state.queue.isEmpty
                ? 'Your changes synced. Reload to refresh the current register.'
                : 'Saved on this device. Sync will retry when the school reconnects.',
          ),
        );
      }
    }
  }

  Future<void> reviewConflict() async {
    if (state.busy || (!state.conflict && !state.blocked)) return;
    final generation = _generation;
    emit(state.copy(busy: true));
    try {
      final current = await repository.matrix(register).timeout(requestTimeout);
      if (!isClosed && generation == _generation) {
        emit(state.copy(review: current, busy: false));
      }
    } catch (error) {
      if (error is ApiException && [401, 403, 404].contains(error.statusCode)) {
        await _forget(
          'Access changed. The cached register was removed.',
          generation,
        );
      } else if (!isClosed && generation == _generation) {
        emit(
          state.copy(
            busy: false,
            error:
                'Could not load the current register. Your queue is retained.',
          ),
        );
      }
    }
  }

  Map<int, String> get intended {
    final values = <int, String>{};
    for (final operation in state.queue) {
      for (final row in operation.rows) {
        values[row.learnerId] = row.state;
      }
    }
    return {...values, ...state.draft};
  }

  Future<void> useReviewedChanges(Set<int> chosen) async {
    final current = state.review;
    if (state.busy || current == null || !current.canWrite) return;
    final generation = _generation;
    final wanted = intended;
    final draft = <int, String>{};
    for (final row in current.learners) {
      if (chosen.contains(row.learnerId) &&
          wanted[row.learnerId] != null &&
          wanted[row.learnerId] != row.state) {
        draft[row.learnerId] = wanted[row.learnerId]!;
      }
    }
    final next = RegisterState(
      matrix: current,
      loading: false,
      draft: Map.unmodifiable(draft),
      sources: {for (final id in draft.keys) id: 'tap'},
      reason: state.reason,
      notice: 'Reviewed changes are a new draft. Save when ready.',
    );
    emit(state.copy(busy: true));
    try {
      await _persist(next);
      if (!isClosed && generation == _generation) emit(next);
    } catch (_) {
      if (!isClosed && generation == _generation) {
        emit(
          state.copy(
            busy: false,
            error:
                'Could not retain the reviewed draft. Your saved queue is unchanged.',
          ),
        );
      }
    }
  }

  Future<void> discardQueued() async {
    if (state.busy) return;
    final generation = _generation;
    emit(state.copy(busy: true));
    try {
      final current =
          state.review ??
          await repository.matrix(register).timeout(requestTimeout);
      if (isClosed || generation != _generation) return;
      final next = RegisterState(
        matrix: current,
        loading: false,
        notice: 'Queued changes discarded after review.',
      );
      await _persist(next);
      if (!isClosed && generation == _generation) emit(next);
    } catch (error) {
      if (error is ApiException && [401, 403, 404].contains(error.statusCode)) {
        await _forget(
          'Access changed. The cached register was removed.',
          generation,
        );
      } else if (!isClosed && generation == _generation) {
        emit(
          state.copy(
            busy: false,
            error: 'Could not discard the saved queue. Retry when connected.',
          ),
        );
      }
    }
  }

  @override
  Future<void> close() {
    _generation++;
    _retry?.cancel();
    if (!isClosed) emit(const RegisterState(loading: false));
    return super.close();
  }
}
