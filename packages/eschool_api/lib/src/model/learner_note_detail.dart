//
// AUTO-GENERATED FILE, DO NOT MODIFY!
//

// ignore_for_file: unused_element
import 'package:json_annotation/json_annotation.dart';

part 'learner_note_detail.g.dart';

@JsonSerializable(
  checked: true,
  createToJson: true,
  disallowUnrecognizedKeys: false,
  explicitToJson: true,
)
class LearnerNoteDetail {
  /// Returns a new [LearnerNoteDetail] instance.
  LearnerNoteDetail({
    required this.id,

    required this.learnerId,

    required this.subjectId,

    required this.sessionYearId,

    required this.authorId,

    required this.authorName,

    required this.visibility,

    required this.revision,

    required this.archived,

    required this.updatedAt,

    required this.body,
  });

  // minimum: 1
  @JsonKey(name: r'id', required: true, includeIfNull: false)
  final int id;

  // minimum: 1
  @JsonKey(name: r'learner_id', required: true, includeIfNull: false)
  final int learnerId;

  // minimum: 1
  @JsonKey(name: r'subject_id', required: true, includeIfNull: true)
  final int? subjectId;

  // minimum: 1
  @JsonKey(name: r'session_year_id', required: true, includeIfNull: false)
  final int sessionYearId;

  // minimum: 1
  @JsonKey(name: r'author_id', required: true, includeIfNull: false)
  final int authorId;

  @JsonKey(name: r'author_name', required: true, includeIfNull: false)
  final String authorName;

  @JsonKey(
    name: r'visibility',
    required: true,
    includeIfNull: false,
    unknownEnumValue: LearnerNoteDetailVisibilityEnum.unknownDefaultOpenApi,
  )
  final LearnerNoteDetailVisibilityEnum visibility;

  // minimum: 1
  @JsonKey(name: r'revision', required: true, includeIfNull: false)
  final int revision;

  @JsonKey(name: r'archived', required: true, includeIfNull: false)
  final bool archived;

  @JsonKey(name: r'updated_at', required: true, includeIfNull: false)
  final DateTime updatedAt;

  @JsonKey(name: r'body', required: true, includeIfNull: false)
  final String body;

  @override
  bool operator ==(Object other) =>
      identical(this, other) ||
      other is LearnerNoteDetail &&
          other.id == id &&
          other.learnerId == learnerId &&
          other.subjectId == subjectId &&
          other.sessionYearId == sessionYearId &&
          other.authorId == authorId &&
          other.authorName == authorName &&
          other.visibility == visibility &&
          other.revision == revision &&
          other.archived == archived &&
          other.updatedAt == updatedAt &&
          other.body == body;

  @override
  int get hashCode =>
      id.hashCode +
      learnerId.hashCode +
      (subjectId == null ? 0 : subjectId.hashCode) +
      sessionYearId.hashCode +
      authorId.hashCode +
      authorName.hashCode +
      visibility.hashCode +
      revision.hashCode +
      archived.hashCode +
      updatedAt.hashCode +
      body.hashCode;

  factory LearnerNoteDetail.fromJson(Map<String, dynamic> json) =>
      _$LearnerNoteDetailFromJson(json);

  Map<String, dynamic> toJson() => _$LearnerNoteDetailToJson(this);

  @override
  String toString() {
    return toJson().toString();
  }
}

enum LearnerNoteDetailVisibilityEnum {
  @JsonValue(r'tutor')
  tutor(r'tutor'),
  @JsonValue(r'staff')
  staff(r'staff'),
  @JsonValue(r'counselling')
  counselling(r'counselling'),
  @JsonValue(r'unknown_default_open_api')
  unknownDefaultOpenApi(r'unknown_default_open_api');

  const LearnerNoteDetailVisibilityEnum(this.value);

  final String value;

  @override
  String toString() => value;
}
