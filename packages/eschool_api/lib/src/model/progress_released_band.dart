//
// AUTO-GENERATED FILE, DO NOT MODIFY!
//

// ignore_for_file: unused_element
import 'package:json_annotation/json_annotation.dart';

part 'progress_released_band.g.dart';

@JsonSerializable(
  checked: true,
  createToJson: true,
  disallowUnrecognizedKeys: false,
  explicitToJson: true,
)
class ProgressReleasedBand {
  /// Returns a new [ProgressReleasedBand] instance.
  ProgressReleasedBand({
    required this.subjectName,

    required this.gradeName,

    required this.releaseId,

    required this.subjectId,

    required this.gradeLevelId,

    required this.sessionYearId,

    required this.termId,

    required this.mode,

    required this.provisional,

    required this.ratingLevel,

    required this.releasedAt,

    required this.releasedPercentageBp,

    required this.band,

    required this.percentageBp,

    required this.coverageBp,

    required this.alertsEligible,

    required this.settingsRevision,
  });

  @JsonKey(name: r'subject_name', required: true, includeIfNull: false)
  final String subjectName;

  @JsonKey(name: r'grade_name', required: true, includeIfNull: false)
  final String gradeName;

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

  // minimum: 1
  @JsonKey(name: r'term_id', required: true, includeIfNull: true)
  final int? termId;

  @JsonKey(
    name: r'mode',
    required: true,
    includeIfNull: false,
    unknownEnumValue: ProgressReleasedBandModeEnum.unknownDefaultOpenApi,
  )
  final ProgressReleasedBandModeEnum mode;

  @JsonKey(name: r'provisional', required: true, includeIfNull: false)
  final bool provisional;

  // minimum: 0
  @JsonKey(name: r'rating_level', required: true, includeIfNull: true)
  final int? ratingLevel;

  @JsonKey(name: r'released_at', required: true, includeIfNull: false)
  final DateTime releasedAt;

  // minimum: 0
  // maximum: 10000
  @JsonKey(name: r'released_percentage_bp', required: true, includeIfNull: true)
  final int? releasedPercentageBp;

  @JsonKey(
    name: r'band',
    required: true,
    includeIfNull: false,
    unknownEnumValue: ProgressReleasedBandBandEnum.unknownDefaultOpenApi,
  )
  final ProgressReleasedBandBandEnum band;

  // minimum: 0
  // maximum: 10000
  @JsonKey(name: r'percentage_bp', required: true, includeIfNull: true)
  final int? percentageBp;

  // minimum: 0
  // maximum: 10000
  @JsonKey(name: r'coverage_bp', required: true, includeIfNull: false)
  final int coverageBp;

  @JsonKey(name: r'alerts_eligible', required: true, includeIfNull: false)
  final bool alertsEligible;

  // minimum: 0
  @JsonKey(name: r'settings_revision', required: true, includeIfNull: false)
  final int settingsRevision;

  @override
  bool operator ==(Object other) =>
      identical(this, other) ||
      other is ProgressReleasedBand &&
          other.subjectName == subjectName &&
          other.gradeName == gradeName &&
          other.releaseId == releaseId &&
          other.subjectId == subjectId &&
          other.gradeLevelId == gradeLevelId &&
          other.sessionYearId == sessionYearId &&
          other.termId == termId &&
          other.mode == mode &&
          other.provisional == provisional &&
          other.ratingLevel == ratingLevel &&
          other.releasedAt == releasedAt &&
          other.releasedPercentageBp == releasedPercentageBp &&
          other.band == band &&
          other.percentageBp == percentageBp &&
          other.coverageBp == coverageBp &&
          other.alertsEligible == alertsEligible &&
          other.settingsRevision == settingsRevision;

  @override
  int get hashCode =>
      subjectName.hashCode +
      gradeName.hashCode +
      releaseId.hashCode +
      subjectId.hashCode +
      gradeLevelId.hashCode +
      sessionYearId.hashCode +
      (termId == null ? 0 : termId.hashCode) +
      mode.hashCode +
      provisional.hashCode +
      (ratingLevel == null ? 0 : ratingLevel.hashCode) +
      releasedAt.hashCode +
      (releasedPercentageBp == null ? 0 : releasedPercentageBp.hashCode) +
      band.hashCode +
      (percentageBp == null ? 0 : percentageBp.hashCode) +
      coverageBp.hashCode +
      alertsEligible.hashCode +
      settingsRevision.hashCode;

  factory ProgressReleasedBand.fromJson(Map<String, dynamic> json) =>
      _$ProgressReleasedBandFromJson(json);

  Map<String, dynamic> toJson() => _$ProgressReleasedBandToJson(this);

  @override
  String toString() {
    return toJson().toString();
  }
}

enum ProgressReleasedBandModeEnum {
  @JsonValue(r'running')
  running(r'running'),
  @JsonValue(r'official')
  official(r'official'),
  @JsonValue(r'unknown_default_open_api')
  unknownDefaultOpenApi(r'unknown_default_open_api');

  const ProgressReleasedBandModeEnum(this.value);

  final String value;

  @override
  String toString() => value;
}

enum ProgressReleasedBandBandEnum {
  @JsonValue(r'high')
  high(r'high'),
  @JsonValue(r'secure')
  secure(r'secure'),
  @JsonValue(r'support')
  support(r'support'),
  @JsonValue(r'insufficient_evidence')
  insufficientEvidence(r'insufficient_evidence'),
  @JsonValue(r'unknown_default_open_api')
  unknownDefaultOpenApi(r'unknown_default_open_api');

  const ProgressReleasedBandBandEnum(this.value);

  final String value;

  @override
  String toString() => value;
}
