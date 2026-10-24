//
// AUTO-GENERATED FILE, DO NOT MODIFY!
//

// ignore_for_file: unused_element
import 'package:eschool_api/src/model/academic_stored_question.dart';
import 'package:json_annotation/json_annotation.dart';

part 'academic_mark.g.dart';

@JsonSerializable(
  checked: true,
  createToJson: true,
  disallowUnrecognizedKeys: false,
  explicitToJson: true,
)
class AcademicMark {
  /// Returns a new [AcademicMark] instance.
  AcademicMark({
    required this.id,

    required this.canonicalTaskId,

    required this.learnerId,

    required this.teachingGroupId,

    required this.mark,

    required this.status,

    required this.disposition,

    required this.revision,

    required this.note,

    required this.capturedBy,

    required this.moderatedBy,

    required this.moderatedAt,

    required this.questionMarks,
  });

  // minimum: 1
  @JsonKey(name: r'id', required: true, includeIfNull: false)
  final int id;

  // minimum: 1
  @JsonKey(name: r'canonical_task_id', required: true, includeIfNull: false)
  final int canonicalTaskId;

  // minimum: 1
  @JsonKey(name: r'learner_id', required: true, includeIfNull: false)
  final int learnerId;

  // minimum: 1
  @JsonKey(name: r'teaching_group_id', required: true, includeIfNull: false)
  final int teachingGroupId;

  @JsonKey(name: r'mark', required: true, includeIfNull: true)
  final String? mark;

  @JsonKey(
    name: r'status',
    required: true,
    includeIfNull: false,
    unknownEnumValue: AcademicMarkStatusEnum.unknownDefaultOpenApi,
  )
  final AcademicMarkStatusEnum status;

  @JsonKey(name: r'disposition', required: true, includeIfNull: false)
  final String disposition;

  // minimum: 1
  @JsonKey(name: r'revision', required: true, includeIfNull: false)
  final int revision;

  @JsonKey(name: r'note', required: true, includeIfNull: true)
  final String? note;

  // minimum: 1
  @JsonKey(name: r'captured_by', required: true, includeIfNull: false)
  final int capturedBy;

  // minimum: 0
  @JsonKey(name: r'moderated_by', required: true, includeIfNull: true)
  final int? moderatedBy;

  @JsonKey(name: r'moderated_at', required: true, includeIfNull: true)
  final String? moderatedAt;

  @JsonKey(name: r'question_marks', required: true, includeIfNull: false)
  final List<AcademicStoredQuestion> questionMarks;

  @override
  bool operator ==(Object other) =>
      identical(this, other) ||
      other is AcademicMark &&
          other.id == id &&
          other.canonicalTaskId == canonicalTaskId &&
          other.learnerId == learnerId &&
          other.teachingGroupId == teachingGroupId &&
          other.mark == mark &&
          other.status == status &&
          other.disposition == disposition &&
          other.revision == revision &&
          other.note == note &&
          other.capturedBy == capturedBy &&
          other.moderatedBy == moderatedBy &&
          other.moderatedAt == moderatedAt &&
          other.questionMarks == questionMarks;

  @override
  int get hashCode =>
      id.hashCode +
      canonicalTaskId.hashCode +
      learnerId.hashCode +
      teachingGroupId.hashCode +
      (mark == null ? 0 : mark.hashCode) +
      status.hashCode +
      disposition.hashCode +
      revision.hashCode +
      (note == null ? 0 : note.hashCode) +
      capturedBy.hashCode +
      (moderatedBy == null ? 0 : moderatedBy.hashCode) +
      (moderatedAt == null ? 0 : moderatedAt.hashCode) +
      questionMarks.hashCode;

  factory AcademicMark.fromJson(Map<String, dynamic> json) =>
      _$AcademicMarkFromJson(json);

  Map<String, dynamic> toJson() => _$AcademicMarkToJson(this);

  @override
  String toString() {
    return toJson().toString();
  }
}

enum AcademicMarkStatusEnum {
  @JsonValue(r'captured')
  captured(r'captured'),
  @JsonValue(r'moderated')
  moderated(r'moderated'),
  @JsonValue(r'locked')
  locked(r'locked'),
  @JsonValue(r'unknown_default_open_api')
  unknownDefaultOpenApi(r'unknown_default_open_api');

  const AcademicMarkStatusEnum(this.value);

  final String value;

  @override
  String toString() => value;
}
