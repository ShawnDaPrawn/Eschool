import 'dart:async';
import 'package:eschool_core/eschool_core.dart';
import 'package:flutter_bloc/flutter_bloc.dart';
import 'mark_draft.dart';
import 'mark_input.dart';
import 'marks_repository.dart';

class MarkGridState {
  const MarkGridState({
    this.group = 0,
    this.term = 0,
    this.matrix,
    this.task,
    this.drafts = const {},
    this.previews = const {},
    this.loading = false,
    this.busy = false,
    this.correction = false,
    this.conflict = false,
    this.error,
    this.saved = false,
    this.previewPending = false,
  });
  final int group, term;
  final AcademicMatrixData? matrix;
  final AcademicMatrixTask? task;
  final Map<int, MarkDraft> drafts;
  final Map<int, AcademicMarkResult> previews;
  final bool loading, busy, correction, conflict, saved, previewPending;
  final ApiException? error;
  bool get dirty => drafts.isNotEmpty;

  MarkGridState copy({
    AcademicMatrixData? matrix,
    AcademicMatrixTask? task,
    Map<int, MarkDraft>? drafts,
    Map<int, AcademicMarkResult>? previews,
    bool? loading,
    bool? busy,
    bool? correction,
    bool? conflict,
    ApiException? error,
    bool? saved,
    bool? previewPending,
  }) => MarkGridState(
    group: group,
    term: term,
    matrix: matrix ?? this.matrix,
    task: task ?? this.task,
    drafts: drafts ?? this.drafts,
    previews: previews ?? this.previews,
    loading: loading ?? this.loading,
    busy: busy ?? this.busy,
    correction: correction ?? this.correction,
    conflict: conflict ?? this.conflict,
    error: error,
    saved: saved ?? false,
    previewPending: previewPending ?? this.previewPending,
  );
}

/// Context-owned drafts and generations prevent late responses crossing schools/tasks.
class MarkGridCubit extends Cubit<MarkGridState> {
  MarkGridCubit(this.repository) : super(const MarkGridState());
  final MarksRepository repository;
  int _generation = 0, _previewGeneration = 0;
  Timer? _timer;
  ApiException _error(Object e) => e is ApiException
      ? e
      : ApiException(
          e is FormatException
              ? e.message
              : ApiErrorCodes.defaultErrorMessageKey,
          isApiMessage: e is FormatException,
        );

  Future<void> load(int group, int term, {int page = 1, int? task}) async {
    final generation = ++_generation;
    _timer?.cancel();
    _previewGeneration++;
    emit(MarkGridState(group: group, term: term, loading: true));
    try {
      final matrix = await repository.matrix(group, term, page: page);
      if (isClosed || generation != _generation) return;
      final selected = matrix.tasks.where((t) => t.id == task);
      emit(
        MarkGridState(
          group: group,
          term: term,
          matrix: matrix,
          task: selected.isNotEmpty ? selected.first : matrix.tasks.firstOrNull,
        ),
      );
    } catch (e) {
      if (!isClosed && generation == _generation) {
        emit(MarkGridState(group: group, term: term, error: _error(e)));
      }
    }
  }

  void selectTask(int id) {
    if (state.busy || state.dirty) return;
    final task = state.matrix?.tasks.where((t) => t.id == id).firstOrNull;
    if (task == null) return;
    _timer?.cancel();
    _previewGeneration++;
    emit(
      state.copy(
        task: task,
        drafts: const {},
        previews: const {},
        conflict: false,
      ),
    );
  }

  MarkDraft draft(AcademicMatrixLearner learner) =>
      state.drafts[learner.learnerId] ?? MarkDraft.saved(learner, state.task!);
  bool canEdit(AcademicMatrixLearner learner) {
    final matrix = state.matrix;
    final task = state.task;
    if (matrix == null ||
        task == null ||
        matrix.locked ||
        state.busy ||
        state.conflict) {
      return false;
    }
    final mark = learner.marks
        .where((m) => m.canonicalTaskId == task.canonicalTaskId)
        .firstOrNull;
    if (mark?.status == AcademicMarkStatusEnum.locked) return false;
    if (state.correction) return matrix.capabilities.correct;
    return matrix.capabilities.capture &&
        mark?.status != AcademicMarkStatusEnum.moderated;
  }

  void correction(bool value) {
    if (state.busy || (value && state.matrix?.capabilities.correct != true)) {
      return;
    }
    emit(state.copy(correction: value));
    _schedulePreview();
  }

  void change(
    AcademicMatrixLearner learner, {
    int? field,
    String? value,
    AcademicMarkRowDispositionEnum? disposition,
    String? note,
  }) {
    if (!canEdit(learner)) return;
    var row = draft(learner);
    final values = Map<int, String>.from(row.values);
    if (field != null) values[field] = value ?? '';
    if ([
      AcademicMarkRowDispositionEnum.absent,
      AcademicMarkRowDispositionEnum.excused,
      AcademicMarkRowDispositionEnum.missing,
    ].contains(disposition)) {
      values.updateAll((_, value) => '');
    }
    row = row.change(values: values, disposition: disposition, note: note);
    emit(
      state.copy(
        drafts: Map.unmodifiable({...state.drafts, learner.learnerId: row}),
      ),
    );
    _schedulePreview();
  }

  void paste(String text, {required int row, required int column}) {
    final task = state.task, matrix = state.matrix;
    if (task == null || matrix == null) return;
    try {
      final paste = markPaste(text);
      final columns = task.questions.isEmpty
          ? [0]
          : task.questions.map((q) => q.id).toList();
      if (row < 0 ||
          column < 0 ||
          row + paste.length > matrix.learners.length ||
          column + paste.first.length > columns.length) {
        throw const FormatException(
          'The pasted selection exceeds the visible rows or questions.',
        );
      }
      final changes = Map<int, MarkDraft>.from(state.drafts);
      for (var r = 0; r < paste.length; r++) {
        final learner = matrix.learners[row + r];
        if (!canEdit(learner)) {
          throw const FormatException(
            'The selection includes a read-only learner.',
          );
        }
        final old = draft(learner);
        if (old.noScore) {
          throw const FormatException(
            'Choose recorded or catch-up before pasting scores.',
          );
        }
        final values = Map<int, String>.from(old.values);
        for (var c = 0; c < paste[r].length; c++) {
          final index = column + c;
          final maximum = task.questions.isEmpty
              ? task.maxMark
              : task.questions[index].maxMark;
          markUnits(paste[r][c], markUnits(maximum, 100000000)!);
          values[columns[index]] = paste[r][c];
        }
        changes[learner.learnerId] = old.change(values: values);
      }
      emit(state.copy(drafts: Map.unmodifiable(changes)));
      _schedulePreview();
    } catch (e) {
      emit(state.copy(previewPending: false, error: _error(e)));
    }
  }

  AcademicMarkWrite _input() => AcademicMarkWrite(
    rows: state.drafts.values
        .map((d) => d.row(state.task!, correction: state.correction))
        .toList(),
  );
  void _schedulePreview() {
    if (!state.dirty) return;
    emit(state.copy(previewPending: true));
    _timer?.cancel();
    _previewGeneration++;
    _timer = Timer(const Duration(milliseconds: 350), preview);
  }

  Future<void> preview() async {
    if (!state.dirty || state.busy || state.task == null) return;
    final generation = _generation, preview = ++_previewGeneration;
    try {
      final result = await repository.preview(
        state.group,
        state.task!.id,
        _input(),
      );
      if (isClosed ||
          generation != _generation ||
          preview != _previewGeneration) {
        return;
      }
      emit(
        state.copy(
          previewPending: false,
          previews: Map.unmodifiable({
            for (final p in result.previews) p.learnerId: p.result,
          }),
        ),
      );
    } catch (e) {
      if (!isClosed &&
          generation == _generation &&
          preview == _previewGeneration) {
        emit(state.copy(previewPending: false, error: _error(e)));
      }
    }
  }

  Future<bool> save() async {
    if (!state.dirty ||
        state.busy ||
        state.conflict ||
        state.task == null ||
        state.matrix!.locked) {
      return false;
    }
    final generation = _generation;
    final task = state.task!.id, page = state.matrix!.currentPage;
    var recorded = false;
    try {
      final input = _input();
      _timer?.cancel();
      _previewGeneration++;
      emit(state.copy(busy: true, previewPending: false));
      await repository.capture(
        state.group,
        task,
        input,
        correction: state.correction,
      );
      recorded = true;
      final matrix = await repository.matrix(
        state.group,
        state.term,
        page: page,
      );
      if (isClosed || generation != _generation) return false;
      emit(
        state.copy(
          matrix: matrix,
          task: matrix.tasks.where((t) => t.id == task).firstOrNull,
          drafts: const {},
          previews: const {},
          busy: false,
          conflict: false,
          saved: true,
        ),
      );
      return true;
    } catch (e) {
      if (!isClosed && generation == _generation) {
        final error = _error(e);
        if (error.statusCode == 403 || error.statusCode == 401) {
          emit(
            MarkGridState(group: state.group, term: state.term, error: error),
          );
        } else {
          emit(
            state.copy(
              busy: false,
              conflict: recorded || error.statusCode == 409,
              error: error,
            ),
          );
        }
      }
      return false;
    }
  }

  Future<void> moderate(Set<int> learners, String reason) async {
    if (state.dirty ||
        state.busy ||
        state.matrix?.capabilities.moderate != true ||
        state.task == null) {
      return;
    }
    final rows = <AcademicModerationRow>[];
    for (final learner in state.matrix!.learners.where(
      (l) => learners.contains(l.learnerId),
    )) {
      final mark = learner.marks
          .where((m) => m.canonicalTaskId == state.task!.canonicalTaskId)
          .firstOrNull;
      if (mark != null) {
        rows.add(
          AcademicModerationRow(
            learnerId: learner.learnerId,
            expectedRevision: mark.revision,
          ),
        );
      }
    }
    if (rows.isEmpty) return;
    final generation = _generation;
    emit(state.copy(busy: true, previewPending: false));
    try {
      await repository.moderate(
        state.group,
        state.task!.id,
        AcademicModerationWrite(reason: reason.trim(), rows: rows),
      );
      if (!isClosed && generation == _generation) {
        await load(
          state.group,
          state.term,
          page: state.matrix!.currentPage,
          task: state.task!.id,
        );
      }
    } catch (e) {
      if (!isClosed && generation == _generation) {
        emit(
          state.copy(
            busy: false,
            error: _error(e),
            conflict: _error(e).statusCode == 409,
          ),
        );
      }
    }
  }

  @override
  Future<void> close() {
    _timer?.cancel();
    _generation++;
    _previewGeneration++;
    return super.close();
  }
}
