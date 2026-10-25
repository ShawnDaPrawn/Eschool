//
// AUTO-GENERATED FILE, DO NOT MODIFY!
//

// ignore_for_file: unused_element
import 'package:json_annotation/json_annotation.dart';

part 'learner_note_create.g.dart';

@JsonSerializable(
  checked: true,
  createToJson: true,
  disallowUnrecognizedKeys: false,
  explicitToJson: true,
)
class LearnerNoteCreate {
  /// Returns a new [LearnerNoteCreate] instance.
  LearnerNoteCreate({
    required this.operationId,

    required this.sessionYearId,

    this.subjectId,

    required this.visibility,

    required this.body,
  });

  @JsonKey(name: r'operation_id', required: true, includeIfNull: false)
  final String operationId;

  // minimum: 1
  @JsonKey(name: r'session_year_id', required: true, includeIfNull: false)
  final int sessionYearId;

  // minimum: 1
  @JsonKey(name: r'subject_id', required: false, includeIfNull: false)
  final int? subjectId;

  @JsonKey(
    name: r'visibility',
    required: true,
    includeIfNull: false,
    unknownEnumValue: LearnerNoteCreateVisibilityEnum.unknownDefaultOpenApi,
  )
  final LearnerNoteCreateVisibilityEnum visibility;

  @JsonKey(name: r'body', required: true, includeIfNull: false)
  final String body;

  @override
  bool operator ==(Object other) =>
      identical(this, other) ||
      other is LearnerNoteCreate &&
          other.operationId == operationId &&
          other.sessionYearId == sessionYearId &&
          other.subjectId == subjectId &&
          other.visibility == visibility &&
          other.body == body;

  @override
  int get hashCode =>
      operationId.hashCode +
      sessionYearId.hashCode +
      (subjectId == null ? 0 : subjectId.hashCode) +
      visibility.hashCode +
      body.hashCode;

  factory LearnerNoteCreate.fromJson(Map<String, dynamic> json) =>
      _$LearnerNoteCreateFromJson(json);

  Map<String, dynamic> toJson() => _$LearnerNoteCreateToJson(this);

  @override
  String toString() {
    return toJson().toString();
  }
}

enum LearnerNoteCreateVisibilityEnum {
  @JsonValue(r'tutor')
  tutor(r'tutor'),
  @JsonValue(r'staff')
  staff(r'staff'),
  @JsonValue(r'counselling')
  counselling(r'counselling'),
  @JsonValue(r'unknown_default_open_api')
  unknownDefaultOpenApi(r'unknown_default_open_api');

  const LearnerNoteCreateVisibilityEnum(this.value);

  final String value;

  @override
  String toString() => value;
}
