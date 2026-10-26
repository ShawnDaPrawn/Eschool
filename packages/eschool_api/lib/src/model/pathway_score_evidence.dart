//
// AUTO-GENERATED FILE, DO NOT MODIFY!
//

// ignore_for_file: unused_element
import 'package:json_annotation/json_annotation.dart';

part 'pathway_score_evidence.g.dart';

@JsonSerializable(
  checked: true,
  createToJson: true,
  disallowUnrecognizedKeys: false,
  explicitToJson: true,
)
class PathwayScoreEvidence {
  /// Returns a new [PathwayScoreEvidence] instance.
  PathwayScoreEvidence({
    required this.subjectId,

    required this.percentageBp,

    required this.points,

    required this.minimumBp,

    required this.countsForScore,
  });

  // minimum: 1
  @JsonKey(name: r'subject_id', required: true, includeIfNull: false)
  final int subjectId;

  // minimum: 0
  // maximum: 10000
  @JsonKey(name: r'percentage_bp', required: true, includeIfNull: true)
  final int? percentageBp;

  // minimum: 0
  // maximum: 20
  @JsonKey(name: r'points', required: true, includeIfNull: true)
  final int? points;

  // minimum: 0
  // maximum: 10000
  @JsonKey(name: r'minimum_bp', required: true, includeIfNull: true)
  final int? minimumBp;

  @JsonKey(name: r'counts_for_score', required: true, includeIfNull: false)
  final bool countsForScore;

  @override
  bool operator ==(Object other) =>
      identical(this, other) ||
      other is PathwayScoreEvidence &&
          other.subjectId == subjectId &&
          other.percentageBp == percentageBp &&
          other.points == points &&
          other.minimumBp == minimumBp &&
          other.countsForScore == countsForScore;

  @override
  int get hashCode =>
      subjectId.hashCode +
      (percentageBp == null ? 0 : percentageBp.hashCode) +
      (points == null ? 0 : points.hashCode) +
      (minimumBp == null ? 0 : minimumBp.hashCode) +
      countsForScore.hashCode;

  factory PathwayScoreEvidence.fromJson(Map<String, dynamic> json) =>
      _$PathwayScoreEvidenceFromJson(json);

  Map<String, dynamic> toJson() => _$PathwayScoreEvidenceToJson(this);

  @override
  String toString() {
    return toJson().toString();
  }
}
