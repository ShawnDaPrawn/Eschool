//
// AUTO-GENERATED FILE, DO NOT MODIFY!
//

// ignore_for_file: unused_element
import 'package:json_annotation/json_annotation.dart';

part 'recognition_nominate.g.dart';

@JsonSerializable(
  checked: true,
  createToJson: true,
  disallowUnrecognizedKeys: false,
  explicitToJson: true,
)
class RecognitionNominate {
  /// Returns a new [RecognitionNominate] instance.
  RecognitionNominate({
    required this.operationId,

    required this.sessionYearId,

    required this.gradeLevelId,

    required this.learnerId,

    required this.awardDefinitionId,

    required this.reason,
  });

  @JsonKey(name: r'operation_id', required: true, includeIfNull: false)
  final String operationId;

  // minimum: 1
  @JsonKey(name: r'session_year_id', required: true, includeIfNull: false)
  final int sessionYearId;

  // minimum: 1
  @JsonKey(name: r'grade_level_id', required: true, includeIfNull: false)
  final int gradeLevelId;

  // minimum: 1
  @JsonKey(name: r'learner_id', required: true, includeIfNull: false)
  final int learnerId;

  // minimum: 1
  @JsonKey(name: r'award_definition_id', required: true, includeIfNull: false)
  final int awardDefinitionId;

  @JsonKey(name: r'reason', required: true, includeIfNull: false)
  final String reason;

  @override
  bool operator ==(Object other) =>
      identical(this, other) ||
      other is RecognitionNominate &&
          other.operationId == operationId &&
          other.sessionYearId == sessionYearId &&
          other.gradeLevelId == gradeLevelId &&
          other.learnerId == learnerId &&
          other.awardDefinitionId == awardDefinitionId &&
          other.reason == reason;

  @override
  int get hashCode =>
      operationId.hashCode +
      sessionYearId.hashCode +
      gradeLevelId.hashCode +
      learnerId.hashCode +
      awardDefinitionId.hashCode +
      reason.hashCode;

  factory RecognitionNominate.fromJson(Map<String, dynamic> json) =>
      _$RecognitionNominateFromJson(json);

  Map<String, dynamic> toJson() => _$RecognitionNominateToJson(this);

  @override
  String toString() {
    return toJson().toString();
  }
}
