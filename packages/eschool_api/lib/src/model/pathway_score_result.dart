//
// AUTO-GENERATED FILE, DO NOT MODIFY!
//

// ignore_for_file: unused_element
import 'package:eschool_api/src/model/pathway_score_evidence.dart';
import 'package:json_annotation/json_annotation.dart';

part 'pathway_score_result.g.dart';

@JsonSerializable(
  checked: true,
  createToJson: true,
  disallowUnrecognizedKeys: false,
  explicitToJson: true,
)
class PathwayScoreResult {
  /// Returns a new [PathwayScoreResult] instance.
  PathwayScoreResult({
    required this.projectedScore,

    required this.knownSubtotal,

    required this.minimumScore,

    required this.status,

    required this.missingSubjectIds,

    required this.belowMinimumSubjectIds,

    required this.countedSubjectIds,

    required this.subjects,
  });

  // minimum: 0
  // maximum: 400
  @JsonKey(name: r'projected_score', required: true, includeIfNull: true)
  final int? projectedScore;

  // minimum: 0
  // maximum: 400
  @JsonKey(name: r'known_subtotal', required: true, includeIfNull: false)
  final int knownSubtotal;

  // minimum: 0
  // maximum: 400
  @JsonKey(name: r'minimum_score', required: true, includeIfNull: false)
  final int minimumScore;

  @JsonKey(
    name: r'status',
    required: true,
    includeIfNull: false,
    unknownEnumValue: PathwayScoreResultStatusEnum.unknownDefaultOpenApi,
  )
  final PathwayScoreResultStatusEnum status;

  @JsonKey(name: r'missing_subject_ids', required: true, includeIfNull: false)
  final List<int> missingSubjectIds;

  @JsonKey(
    name: r'below_minimum_subject_ids',
    required: true,
    includeIfNull: false,
  )
  final List<int> belowMinimumSubjectIds;

  @JsonKey(name: r'counted_subject_ids', required: true, includeIfNull: false)
  final List<int> countedSubjectIds;

  @JsonKey(name: r'subjects', required: true, includeIfNull: false)
  final List<PathwayScoreEvidence> subjects;

  @override
  bool operator ==(Object other) =>
      identical(this, other) ||
      other is PathwayScoreResult &&
          other.projectedScore == projectedScore &&
          other.knownSubtotal == knownSubtotal &&
          other.minimumScore == minimumScore &&
          other.status == status &&
          other.missingSubjectIds == missingSubjectIds &&
          other.belowMinimumSubjectIds == belowMinimumSubjectIds &&
          other.countedSubjectIds == countedSubjectIds &&
          other.subjects == subjects;

  @override
  int get hashCode =>
      (projectedScore == null ? 0 : projectedScore.hashCode) +
      knownSubtotal.hashCode +
      minimumScore.hashCode +
      status.hashCode +
      missingSubjectIds.hashCode +
      belowMinimumSubjectIds.hashCode +
      countedSubjectIds.hashCode +
      subjects.hashCode;

  factory PathwayScoreResult.fromJson(Map<String, dynamic> json) =>
      _$PathwayScoreResultFromJson(json);

  Map<String, dynamic> toJson() => _$PathwayScoreResultToJson(this);

  @override
  String toString() {
    return toJson().toString();
  }
}

enum PathwayScoreResultStatusEnum {
  @JsonValue(r'unknown')
  unknown(r'unknown'),
  @JsonValue(r'below_published_minimum')
  belowPublishedMinimum(r'below_published_minimum'),
  @JsonValue(r'meets_published_minimum')
  meetsPublishedMinimum(r'meets_published_minimum'),
  @JsonValue(r'unknown_default_open_api')
  unknownDefaultOpenApi(r'unknown_default_open_api');

  const PathwayScoreResultStatusEnum(this.value);

  final String value;

  @override
  String toString() => value;
}
