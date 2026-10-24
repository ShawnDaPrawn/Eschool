//
// AUTO-GENERATED FILE, DO NOT MODIFY!
//

// ignore_for_file: unused_element
import 'package:eschool_api/src/model/academic_transition_result.dart';
import 'package:json_annotation/json_annotation.dart';

part 'academic_transition_record.g.dart';

@JsonSerializable(
  checked: true,
  createToJson: true,
  disallowUnrecognizedKeys: false,
  explicitToJson: true,
)
class AcademicTransitionRecord {
  /// Returns a new [AcademicTransitionRecord] instance.
  AcademicTransitionRecord({
    required this.id,

    required this.learnerId,

    required this.sessionYearId,

    required this.gradeLevelId,

    required this.kind,

    required this.effectiveOn,

    required this.reason,

    required this.operationId,

    required this.result,

    required this.createdAt,
  });

  // minimum: 1
  @JsonKey(name: r'id', required: true, includeIfNull: false)
  final int id;

  // minimum: 1
  @JsonKey(name: r'learner_id', required: true, includeIfNull: false)
  final int learnerId;

  // minimum: 1
  @JsonKey(name: r'session_year_id', required: true, includeIfNull: false)
  final int sessionYearId;

  // minimum: 1
  @JsonKey(name: r'grade_level_id', required: true, includeIfNull: true)
  final int? gradeLevelId;

  @JsonKey(name: r'kind', required: true, includeIfNull: false)
  final String kind;

  @JsonKey(name: r'effective_on', required: true, includeIfNull: false)
  final String effectiveOn;

  @JsonKey(name: r'reason', required: true, includeIfNull: false)
  final String reason;

  @JsonKey(name: r'operation_id', required: true, includeIfNull: false)
  final String operationId;

  @JsonKey(name: r'result', required: true, includeIfNull: false)
  final AcademicTransitionResult result;

  @JsonKey(name: r'created_at', required: true, includeIfNull: false)
  final DateTime createdAt;

  @override
  bool operator ==(Object other) =>
      identical(this, other) ||
      other is AcademicTransitionRecord &&
          other.id == id &&
          other.learnerId == learnerId &&
          other.sessionYearId == sessionYearId &&
          other.gradeLevelId == gradeLevelId &&
          other.kind == kind &&
          other.effectiveOn == effectiveOn &&
          other.reason == reason &&
          other.operationId == operationId &&
          other.result == result &&
          other.createdAt == createdAt;

  @override
  int get hashCode =>
      id.hashCode +
      learnerId.hashCode +
      sessionYearId.hashCode +
      (gradeLevelId == null ? 0 : gradeLevelId.hashCode) +
      kind.hashCode +
      effectiveOn.hashCode +
      reason.hashCode +
      operationId.hashCode +
      result.hashCode +
      createdAt.hashCode;

  factory AcademicTransitionRecord.fromJson(Map<String, dynamic> json) =>
      _$AcademicTransitionRecordFromJson(json);

  Map<String, dynamic> toJson() => _$AcademicTransitionRecordToJson(this);

  @override
  String toString() {
    return toJson().toString();
  }
}
