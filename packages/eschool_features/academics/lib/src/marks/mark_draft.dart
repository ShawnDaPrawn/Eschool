import 'mark_input.dart';
import 'marks_repository.dart';

class MarkDraft {
  const MarkDraft({
    required this.learner,
    required this.revision,
    required this.values,
    this.disposition = AcademicMarkRowDispositionEnum.recorded,
    this.note = '',
  });
  final int learner;
  final int revision;
  final Map<int, String> values;
  final AcademicMarkRowDispositionEnum disposition;
  final String note;

  factory MarkDraft.saved(
    AcademicMatrixLearner learner,
    AcademicMatrixTask task,
  ) {
    final marks = learner.marks.where(
      (m) => m.canonicalTaskId == task.canonicalTaskId,
    );
    final mark = marks.isEmpty ? null : marks.single;
    final values = <int, String>{};
    if (task.questions.isEmpty) {
      values[0] = mark?.mark ?? '';
    } else {
      for (final question in task.questions) {
        final scores = mark?.questionMarks.where(
          (q) => q.canonicalQuestionId == question.canonicalQuestionId,
        );
        values[question.id] = scores == null || scores.isEmpty
            ? ''
            : scores.single.mark ?? '';
      }
    }
    return MarkDraft(
      learner: learner.learnerId,
      revision: mark?.revision ?? 0,
      values: Map.unmodifiable(values),
      note: mark?.note ?? '',
      disposition: AcademicMarkRowDispositionEnum.values.firstWhere(
        (v) => v.value == mark?.disposition,
        orElse: () => AcademicMarkRowDispositionEnum.recorded,
      ),
    );
  }

  MarkDraft change({
    Map<int, String>? values,
    AcademicMarkRowDispositionEnum? disposition,
    String? note,
  }) => MarkDraft(
    learner: learner,
    revision: revision,
    values: Map.unmodifiable(values ?? this.values),
    disposition: disposition ?? this.disposition,
    note: note ?? this.note,
  );

  bool get noScore => [
    AcademicMarkRowDispositionEnum.absent,
    AcademicMarkRowDispositionEnum.excused,
    AcademicMarkRowDispositionEnum.missing,
  ].contains(disposition);

  AcademicMarkRow row(AcademicMatrixTask task, {bool correction = false}) {
    if ((correction ||
            [
              AcademicMarkRowDispositionEnum.absent,
              AcademicMarkRowDispositionEnum.excused,
              AcademicMarkRowDispositionEnum.catchUp,
            ].contains(disposition)) &&
        note.trim().isEmpty) {
      throw const FormatException('Record a reason for this change.');
    }
    if (task.questions.isEmpty) {
      final units = markUnits(
        values[0] ?? '',
        markUnits(task.maxMark, 100000000)!,
      );
      if (noScore && units != null) {
        throw const FormatException('Clear the score for this disposition.');
      }
      return AcademicMarkRow(
        learnerId: learner,
        expectedRevision: revision,
        disposition: disposition,
        mark: units == null ? null : markDecimal(units),
        note: note.trim().isEmpty ? null : note.trim(),
        questionMarks: const [],
      );
    }
    final questions = <AcademicQuestionScore>[];
    for (final q in task.questions) {
      final units = markUnits(
        values[q.id] ?? '',
        markUnits(q.maxMark, 100000000)!,
      );
      if (noScore && units != null) {
        throw const FormatException(
          'Clear the question scores for this disposition.',
        );
      }
      questions.add(
        AcademicQuestionScore(
          questionId: q.id,
          mark: units == null ? null : markDecimal(units),
        ),
      );
    }
    return AcademicMarkRow(
      learnerId: learner,
      expectedRevision: revision,
      disposition: disposition,
      mark: null,
      note: note.trim().isEmpty ? null : note.trim(),
      questionMarks: questions,
    );
  }
}
