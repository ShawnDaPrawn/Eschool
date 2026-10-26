//
// AUTO-GENERATED FILE, DO NOT MODIFY!
//

// ignore_for_file: unused_element
import 'package:json_annotation/json_annotation.dart';

part 'pathway_released_evidence.g.dart';

@JsonSerializable(
  checked: true,
  createToJson: true,
  disallowUnrecognizedKeys: false,
  explicitToJson: true,
)
class PathwayReleasedEvidence {
  /// Returns a new [PathwayReleasedEvidence] instance.
  PathwayReleasedEvidence({
    required this.subjectId,

    required this.percentageBp,

    required this.releaseId,

    required this.gradeName,

    required this.provisional,

    required this.coverageBp,

    required this.releasedAt,
  });

  // minimum: 1
  @JsonKey(name: r'subject_id', required: true, includeIfNull: false)
  final int subjectId;

  // minimum: 0
  // maximum: 10000
  @JsonKey(name: r'percentage_bp', required: true, includeIfNull: true)
  final int? percentageBp;

  // minimum: 1
  @JsonKey(name: r'release_id', required: true, includeIfNull: false)
  final int releaseId;

  @JsonKey(name: r'grade_name', required: true, includeIfNull: false)
  final String gradeName;

  @JsonKey(name: r'provisional', required: true, includeIfNull: false)
  final bool provisional;

  // minimum: 0
  // maximum: 10000
  @JsonKey(name: r'coverage_bp', required: true, includeIfNull: false)
  final int coverageBp;

  @JsonKey(name: r'released_at', required: true, includeIfNull: false)
  final DateTime releasedAt;

  @override
  bool operator ==(Object other) =>
      identical(this, other) ||
      other is PathwayReleasedEvidence &&
          other.subjectId == subjectId &&
          other.percentageBp == percentageBp &&
          other.releaseId == releaseId &&
          other.gradeName == gradeName &&
          other.provisional == provisional &&
          other.coverageBp == coverageBp &&
          other.releasedAt == releasedAt;

  @override
  int get hashCode =>
      subjectId.hashCode +
      (percentageBp == null ? 0 : percentageBp.hashCode) +
      releaseId.hashCode +
      gradeName.hashCode +
      provisional.hashCode +
      coverageBp.hashCode +
      releasedAt.hashCode;

  factory PathwayReleasedEvidence.fromJson(Map<String, dynamic> json) =>
      _$PathwayReleasedEvidenceFromJson(json);

  Map<String, dynamic> toJson() => _$PathwayReleasedEvidenceToJson(this);

  @override
  String toString() {
    return toJson().toString();
  }
}
