//
// AUTO-GENERATED FILE, DO NOT MODIFY!
//

// ignore_for_file: unused_element
import 'package:eschool_api/src/model/academic_question_score.dart';
import 'package:json_annotation/json_annotation.dart';

part 'academic_mark_row.g.dart';

@JsonSerializable(
  checked: true,
  createToJson: true,
  disallowUnrecognizedKeys: false,
  explicitToJson: true,
)
class AcademicMarkRow {
  /// Returns a new [AcademicMarkRow] instance.
  AcademicMarkRow({
    required this.learnerId,

    required this.expectedRevision,

    required this.disposition,

    required this.mark,

    this.note,

    required this.questionMarks,
  });

  // minimum: 1
  @JsonKey(name: r'learner_id', required: true, includeIfNull: false)
  final int learnerId;

  // minimum: 0
  @JsonKey(name: r'expected_revision', required: true, includeIfNull: false)
  final int expectedRevision;

  @JsonKey(
    name: r'disposition',
    required: true,
    includeIfNull: false,
    unknownEnumValue: AcademicMarkRowDispositionEnum.unknownDefaultOpenApi,
  )
  final AcademicMarkRowDispositionEnum disposition;

  @JsonKey(name: r'mark', required: true, includeIfNull: true)
  final String? mark;

  @JsonKey(name: r'note', required: false, includeIfNull: false)
  final String? note;

  @JsonKey(name: r'question_marks', required: true, includeIfNull: false)
  final List<AcademicQuestionScore> questionMarks;

  @override
  bool operator ==(Object other) =>
      identical(this, other) ||
      other is AcademicMarkRow &&
          other.learnerId == learnerId &&
          other.expectedRevision == expectedRevision &&
          other.disposition == disposition &&
          other.mark == mark &&
          other.note == note &&
          other.questionMarks == questionMarks;

  @override
  int get hashCode =>
      learnerId.hashCode +
      expectedRevision.hashCode +
      disposition.hashCode +
      (mark == null ? 0 : mark.hashCode) +
      (note == null ? 0 : note.hashCode) +
      questionMarks.hashCode;

  factory AcademicMarkRow.fromJson(Map<String, dynamic> json) =>
      _$AcademicMarkRowFromJson(json);

  Map<String, dynamic> toJson() => _$AcademicMarkRowToJson(this);

  @override
  String toString() {
    return toJson().toString();
  }
}

enum AcademicMarkRowDispositionEnum {
  @JsonValue(r'recorded')
  recorded(r'recorded'),
  @JsonValue(r'missing')
  missing(r'missing'),
  @JsonValue(r'absent')
  absent(r'absent'),
  @JsonValue(r'excused')
  excused(r'excused'),
  @JsonValue(r'catch_up')
  catchUp(r'catch_up'),
  @JsonValue(r'unknown_default_open_api')
  unknownDefaultOpenApi(r'unknown_default_open_api');

  const AcademicMarkRowDispositionEnum(this.value);

  final String value;

  @override
  String toString() => value;
}
