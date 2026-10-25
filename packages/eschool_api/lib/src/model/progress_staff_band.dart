//
// AUTO-GENERATED FILE, DO NOT MODIFY!
//

// ignore_for_file: unused_element
import 'package:json_annotation/json_annotation.dart';

part 'progress_staff_band.g.dart';

@JsonSerializable(
  checked: true,
  createToJson: true,
  disallowUnrecognizedKeys: false,
  explicitToJson: true,
)
class ProgressStaffBand {
  /// Returns a new [ProgressStaffBand] instance.
  ProgressStaffBand({
    required this.audience,

    required this.learnerId,

    required this.subjectId,

    required this.sessionYearId,

    required this.termId,

    required this.snapshotId,

    required this.provisional,

    required this.band,

    required this.percentageBp,

    required this.coverageBp,

    required this.alertsEligible,

    required this.settingsRevision,
  });

  @JsonKey(
    name: r'audience',
    required: true,
    includeIfNull: false,
    unknownEnumValue: ProgressStaffBandAudienceEnum.unknownDefaultOpenApi,
  )
  final ProgressStaffBandAudienceEnum audience;

  // minimum: 1
  @JsonKey(name: r'learner_id', required: true, includeIfNull: false)
  final int learnerId;

  // minimum: 1
  @JsonKey(name: r'subject_id', required: true, includeIfNull: false)
  final int subjectId;

  // minimum: 1
  @JsonKey(name: r'session_year_id', required: true, includeIfNull: false)
  final int sessionYearId;

  // minimum: 1
  @JsonKey(name: r'term_id', required: true, includeIfNull: false)
  final int termId;

  // minimum: 1
  @JsonKey(name: r'snapshot_id', required: true, includeIfNull: false)
  final int snapshotId;

  @JsonKey(name: r'provisional', required: true, includeIfNull: false)
  final bool provisional;

  @JsonKey(
    name: r'band',
    required: true,
    includeIfNull: false,
    unknownEnumValue: ProgressStaffBandBandEnum.unknownDefaultOpenApi,
  )
  final ProgressStaffBandBandEnum band;

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
      other is ProgressStaffBand &&
          other.audience == audience &&
          other.learnerId == learnerId &&
          other.subjectId == subjectId &&
          other.sessionYearId == sessionYearId &&
          other.termId == termId &&
          other.snapshotId == snapshotId &&
          other.provisional == provisional &&
          other.band == band &&
          other.percentageBp == percentageBp &&
          other.coverageBp == coverageBp &&
          other.alertsEligible == alertsEligible &&
          other.settingsRevision == settingsRevision;

  @override
  int get hashCode =>
      audience.hashCode +
      learnerId.hashCode +
      subjectId.hashCode +
      sessionYearId.hashCode +
      termId.hashCode +
      snapshotId.hashCode +
      provisional.hashCode +
      band.hashCode +
      (percentageBp == null ? 0 : percentageBp.hashCode) +
      coverageBp.hashCode +
      alertsEligible.hashCode +
      settingsRevision.hashCode;

  factory ProgressStaffBand.fromJson(Map<String, dynamic> json) =>
      _$ProgressStaffBandFromJson(json);

  Map<String, dynamic> toJson() => _$ProgressStaffBandToJson(this);

  @override
  String toString() {
    return toJson().toString();
  }
}

enum ProgressStaffBandAudienceEnum {
  @JsonValue(r'staff')
  staff(r'staff'),
  @JsonValue(r'unknown_default_open_api')
  unknownDefaultOpenApi(r'unknown_default_open_api');

  const ProgressStaffBandAudienceEnum(this.value);

  final String value;

  @override
  String toString() => value;
}

enum ProgressStaffBandBandEnum {
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

  const ProgressStaffBandBandEnum(this.value);

  final String value;

  @override
  String toString() => value;
}
