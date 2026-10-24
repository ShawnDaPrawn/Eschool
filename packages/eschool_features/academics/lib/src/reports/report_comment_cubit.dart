import 'dart:convert';
import 'package:eschool_api/eschool_api.dart';
import 'package:eschool_core/eschool_core.dart';
import 'package:flutter_bloc/flutter_bloc.dart';
import 'package:uuid/uuid.dart';
import 'report_repository.dart';
import 'report_family_cubit.dart';

/// A retry keeps its UUID until the exact request changes or succeeds.
class ReportOperationKeys {
  final Map<String, String> _keys = {};
  String key(String action, Object body) =>
      _keys.putIfAbsent('$action:${jsonEncode(body)}', () => const Uuid().v4());
  void completed(String action, Object body) =>
      _keys.remove('$action:${jsonEncode(body)}');
}

class ReportCommentState {
  const ReportCommentState({
    required this.text,
    required this.saved,
    required this.revision,
    required this.batchRevision,
    this.busy = false,
    this.error,
    this.conflict = false,
  });
  final String text, saved;
  final int revision, batchRevision;
  final bool busy, conflict;
  final String? error;
  bool get dirty => text != saved;
}

class ReportCommentCubit extends Cubit<ReportCommentState> {
  ReportCommentCubit(this.repository, this.row)
    : super(
        ReportCommentState(
          text: row.text,
          saved: row.text,
          revision: row.revision,
          batchRevision: row.batchRevision,
        ),
      );
  final ReportRepository repository;
  final AcademicReportCommentRow row;
  final _keys = ReportOperationKeys();
  void change(String text) {
    if (state.busy) return;
    emit(
      ReportCommentState(
        text: text,
        saved: state.saved,
        revision: state.revision,
        batchRevision: state.batchRevision,
        conflict: state.conflict,
        error: state.error,
      ),
    );
  }

  Future<void> save({String? reason}) async {
    if (state.busy || !state.dirty || state.conflict) return;
    final old = state;
    final body = AcademicReportCommentWrite(
      subjectId: row.subjectId,
      text: old.text,
      revision: old.revision,
      batchRevision: old.batchRevision,
      reason: reason,
    );
    emit(
      ReportCommentState(
        text: old.text,
        saved: old.saved,
        revision: old.revision,
        batchRevision: old.batchRevision,
        busy: true,
      ),
    );
    try {
      final result = await repository.comment(
        row.cardId,
        body,
        _keys.key('comment:${row.cardId}', body.toJson()),
      );
      if (isClosed) return;
      _keys.completed('comment:${row.cardId}', body.toJson());
      emit(
        ReportCommentState(
          text: result.text,
          saved: result.text,
          revision: result.revision,
          batchRevision: result.batchRevision,
        ),
      );
    } catch (error) {
      if (!isClosed) {
        emit(
          ReportCommentState(
            text: old.text,
            saved: old.saved,
            revision: old.revision,
            batchRevision: old.batchRevision,
            error: reportFailure(error),
            conflict: error is ApiException && error.statusCode == 409,
          ),
        );
      }
    }
  }

  /// Rebase is explicit after displaying the latest saved comment beside the draft.
  void reviewed(AcademicReportCommentRow latest, {required bool keepDraft}) {
    if (state.busy ||
        latest.cardId != row.cardId ||
        latest.subjectId != row.subjectId) {
      return;
    }
    emit(
      ReportCommentState(
        text: keepDraft ? state.text : latest.text,
        saved: latest.text,
        revision: latest.revision,
        batchRevision: latest.batchRevision,
      ),
    );
  }
}
