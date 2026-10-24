//
// AUTO-GENERATED FILE, DO NOT MODIFY!
//

// ignore_for_file: unused_element
import 'package:json_annotation/json_annotation.dart';

part 'academic_released_result.g.dart';

@JsonSerializable(
  checked: true,
  createToJson: true,
  disallowUnrecognizedKeys: false,
  explicitToJson: true,
)
class AcademicReleasedResult {
  /// Returns a new [AcademicReleasedResult] instance.
  AcademicReleasedResult({
    required this.releaseId,

    required this.subjectId,

    required this.gradeLevelId,

    required this.sessionYearId,

    required this.termId,

    required this.mode,

    required this.provisional,

    required this.percentageBp,

    required this.ratingLevel,

    required this.coverageBp,

    required this.releasedAt,
  });

  // minimum: 1
  @JsonKey(name: r'release_id', required: true, includeIfNull: false)
  final int releaseId;

  // minimum: 1
  @JsonKey(name: r'subject_id', required: true, includeIfNull: false)
  final int subjectId;

  // minimum: 1
  @JsonKey(name: r'grade_level_id', required: true, includeIfNull: false)
  final int gradeLevelId;

  // minimum: 1
  @JsonKey(name: r'session_year_id', required: true, includeIfNull: false)
  final int sessionYearId;

  // minimum: 0
  @JsonKey(name: r'term_id', required: true, includeIfNull: true)
  final int? termId;

  @JsonKey(name: r'mode', required: true, includeIfNull: false)
  final String mode;

  @JsonKey(name: r'provisional', required: true, includeIfNull: false)
  final bool provisional;

  // minimum: 0
  @JsonKey(name: r'percentage_bp', required: true, includeIfNull: true)
  final int? percentageBp;

  // minimum: 0
  @JsonKey(name: r'rating_level', required: true, includeIfNull: true)
  final int? ratingLevel;

  @JsonKey(name: r'coverage_bp', required: true, includeIfNull: false)
  final int coverageBp;

  @JsonKey(name: r'released_at', required: true, includeIfNull: false)
  final String releasedAt;

  @override
  bool operator ==(Object other) =>
      identical(this, other) ||
      other is AcademicReleasedResult &&
          other.releaseId == releaseId &&
          other.subjectId == subjectId &&
          other.gradeLevelId == gradeLevelId &&
          other.sessionYearId == sessionYearId &&
          other.termId == termId &&
          other.mode == mode &&
          other.provisional == provisional &&
          other.percentageBp == percentageBp &&
          other.ratingLevel == ratingLevel &&
          other.coverageBp == coverageBp &&
          other.releasedAt == releasedAt;

  @override
  int get hashCode =>
      releaseId.hashCode +
      subjectId.hashCode +
      gradeLevelId.hashCode +
      sessionYearId.hashCode +
      (termId == null ? 0 : termId.hashCode) +
      mode.hashCode +
      provisional.hashCode +
      (percentageBp == null ? 0 : percentageBp.hashCode) +
      (ratingLevel == null ? 0 : ratingLevel.hashCode) +
      coverageBp.hashCode +
      releasedAt.hashCode;

  factory AcademicReleasedResult.fromJson(Map<String, dynamic> json) =>
      _$AcademicReleasedResultFromJson(json);

  Map<String, dynamic> toJson() => _$AcademicReleasedResultToJson(this);

  @override
  String toString() {
    return toJson().toString();
  }
}
