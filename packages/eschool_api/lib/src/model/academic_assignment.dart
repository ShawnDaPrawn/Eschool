//
// AUTO-GENERATED FILE, DO NOT MODIFY!
//

// ignore_for_file: unused_element
import 'package:json_annotation/json_annotation.dart';

part 'academic_assignment.g.dart';

@JsonSerializable(
  checked: true,
  createToJson: true,
  disallowUnrecognizedKeys: false,
  explicitToJson: true,
)
class AcademicAssignment {
  /// Returns a new [AcademicAssignment] instance.
  AcademicAssignment({
    required this.id,

    required this.startsOn,

    required this.endsOn,

    required this.reason,

    required this.revision,

    this.learnerId,

    this.teacherId,

    this.classSectionId,

    this.sessionYearId,

    this.userId,

    this.gradeLevelId,

    this.kind,

    this.markRule,

    this.teachingGroupId,

    required this.displayName,
  });

  // minimum: 1
  @JsonKey(name: r'id', required: true, includeIfNull: false)
  final int id;

  @JsonKey(name: r'starts_on', required: true, includeIfNull: false)
  final String startsOn;

  @JsonKey(name: r'ends_on', required: true, includeIfNull: false)
  final String endsOn;

  @JsonKey(name: r'reason', required: true, includeIfNull: false)
  final String reason;

  // minimum: 1
  @JsonKey(name: r'revision', required: true, includeIfNull: false)
  final int revision;

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
    unknownEnumValue: AcademicAssignmentKindEnum.unknownDefaultOpenApi,
  )
  final AcademicAssignmentKindEnum? kind;

  @JsonKey(
    name: r'mark_rule',
    required: false,
    includeIfNull: false,
    unknownEnumValue: AcademicAssignmentMarkRuleEnum.unknownDefaultOpenApi,
  )
  final AcademicAssignmentMarkRuleEnum? markRule;

  // minimum: 1
  @JsonKey(name: r'teaching_group_id', required: false, includeIfNull: false)
  final int? teachingGroupId;

  @JsonKey(name: r'display_name', required: true, includeIfNull: false)
  final String displayName;

  @override
  bool operator ==(Object other) =>
      identical(this, other) ||
      other is AcademicAssignment &&
          other.id == id &&
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
          other.markRule == markRule &&
          other.teachingGroupId == teachingGroupId &&
          other.displayName == displayName;

  @override
  int get hashCode =>
      id.hashCode +
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
      markRule.hashCode +
      teachingGroupId.hashCode +
      displayName.hashCode;

  factory AcademicAssignment.fromJson(Map<String, dynamic> json) =>
      _$AcademicAssignmentFromJson(json);

  Map<String, dynamic> toJson() => _$AcademicAssignmentToJson(this);

  @override
  String toString() {
    return toJson().toString();
  }
}

enum AcademicAssignmentKindEnum {
  @JsonValue(r'grade_head')
  gradeHead(r'grade_head'),
  @JsonValue(r'principal')
  principal(r'principal'),
  @JsonValue(r'unknown_default_open_api')
  unknownDefaultOpenApi(r'unknown_default_open_api');

  const AcademicAssignmentKindEnum(this.value);

  final String value;

  @override
  String toString() => value;
}

enum AcademicAssignmentMarkRuleEnum {
  @JsonValue(r'catch_up')
  catchUp(r'catch_up'),
  @JsonValue(r'excused')
  excused(r'excused'),
  @JsonValue(r'carried')
  carried(r'carried'),
  @JsonValue(r'unknown_default_open_api')
  unknownDefaultOpenApi(r'unknown_default_open_api');

  const AcademicAssignmentMarkRuleEnum(this.value);

  final String value;

  @override
  String toString() => value;
}
