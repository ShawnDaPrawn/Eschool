//
// AUTO-GENERATED FILE, DO NOT MODIFY!
//

// ignore_for_file: unused_element
import 'package:json_annotation/json_annotation.dart';

part 'academic_assignment_write.g.dart';

@JsonSerializable(
  checked: true,
  createToJson: true,
  disallowUnrecognizedKeys: false,
  explicitToJson: true,
)
class AcademicAssignmentWrite {
  /// Returns a new [AcademicAssignmentWrite] instance.
  AcademicAssignmentWrite({
    required this.startsOn,

    required this.endsOn,

    required this.reason,

    this.revision,

    this.learnerId,

    this.teacherId,

    this.classSectionId,

    this.sessionYearId,

    this.userId,

    this.gradeLevelId,

    this.kind,

    this.markRule,
  });

  @JsonKey(name: r'starts_on', required: true, includeIfNull: false)
  final String startsOn;

  @JsonKey(name: r'ends_on', required: true, includeIfNull: false)
  final String endsOn;

  @JsonKey(name: r'reason', required: true, includeIfNull: false)
  final String reason;

  // minimum: 1
  @JsonKey(name: r'revision', required: false, includeIfNull: false)
  final int? revision;

  // minimum: 1
  @JsonKey(name: r'learner_id', required: false, includeIfNull: false)
  final int? learnerId;

  // minimum: 1
  @JsonKey(name: r'teacher_id', required: false, includeIfNull: false)
  final int? teacherId;

  // minimum: 1
  @JsonKey(name: r'class_section_id', required: false, includeIfNull: false)
  final int? classSectionId;

  // minimum: 1
  @JsonKey(name: r'session_year_id', required: false, includeIfNull: false)
  final int? sessionYearId;

  // minimum: 1
  @JsonKey(name: r'user_id', required: false, includeIfNull: false)
  final int? userId;

  // minimum: 1
  @JsonKey(name: r'grade_level_id', required: false, includeIfNull: false)
  final int? gradeLevelId;

  @JsonKey(
    name: r'kind',
    required: false,
    includeIfNull: false,
    unknownEnumValue: AcademicAssignmentWriteKindEnum.unknownDefaultOpenApi,
  )
  final AcademicAssignmentWriteKindEnum? kind;

  @JsonKey(
    name: r'mark_rule',
    required: false,
    includeIfNull: false,
    unknownEnumValue: AcademicAssignmentWriteMarkRuleEnum.unknownDefaultOpenApi,
  )
  final AcademicAssignmentWriteMarkRuleEnum? markRule;

  @override
  bool operator ==(Object other) =>
      identical(this, other) ||
      other is AcademicAssignmentWrite &&
          other.startsOn == startsOn &&
          other.endsOn == endsOn &&
          other.reason == reason &&
          other.revision == revision &&
          other.learnerId == learnerId &&
          other.teacherId == teacherId &&
          other.classSectionId == classSectionId &&
          other.sessionYearId == sessionYearId &&
          other.userId == userId &&
          other.gradeLevelId == gradeLevelId &&
          other.kind == kind &&
          other.markRule == markRule;

  @override
  int get hashCode =>
      startsOn.hashCode +
      endsOn.hashCode +
      reason.hashCode +
      revision.hashCode +
      learnerId.hashCode +
      teacherId.hashCode +
      classSectionId.hashCode +
      sessionYearId.hashCode +
      userId.hashCode +
      (gradeLevelId == null ? 0 : gradeLevelId.hashCode) +
      kind.hashCode +
      markRule.hashCode;

  factory AcademicAssignmentWrite.fromJson(Map<String, dynamic> json) =>
      _$AcademicAssignmentWriteFromJson(json);

  Map<String, dynamic> toJson() => _$AcademicAssignmentWriteToJson(this);

  @override
  String toString() {
    return toJson().toString();
  }
}

enum AcademicAssignmentWriteKindEnum {
  @JsonValue(r'grade_head')
  gradeHead(r'grade_head'),
  @JsonValue(r'principal')
  principal(r'principal'),
  @JsonValue(r'unknown_default_open_api')
  unknownDefaultOpenApi(r'unknown_default_open_api');

  const AcademicAssignmentWriteKindEnum(this.value);

  final String value;

  @override
  String toString() => value;
}

enum AcademicAssignmentWriteMarkRuleEnum {
  @JsonValue(r'catch_up')
  catchUp(r'catch_up'),
  @JsonValue(r'excused')
  excused(r'excused'),
  @JsonValue(r'carried')
  carried(r'carried'),
  @JsonValue(r'unknown_default_open_api')
  unknownDefaultOpenApi(r'unknown_default_open_api');

  const AcademicAssignmentWriteMarkRuleEnum(this.value);

  final String value;

  @override
  String toString() => value;
}
