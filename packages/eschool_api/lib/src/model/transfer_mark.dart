//
// AUTO-GENERATED FILE, DO NOT MODIFY!
//

// ignore_for_file: unused_element
import 'package:json_annotation/json_annotation.dart';

part 'transfer_mark.g.dart';

@JsonSerializable(
  checked: true,
  createToJson: true,
  disallowUnrecognizedKeys: false,
  explicitToJson: true,
)
class TransferMark {
  /// Returns a new [TransferMark] instance.
  TransferMark({
    required this.releaseId,

    required this.releaseVersion,

    required this.subjectName,

    required this.gradeLevelId,

    required this.sessionYearId,

    required this.termId,

    required this.mode,

    required this.percentageBp,

    required this.ratingLevel,

    required this.coverageBp,

    required this.fingerprint,

    required this.releasedAt,
  });

  // minimum: 1
  @JsonKey(name: r'release_id', required: true, includeIfNull: false)
  final int releaseId;

  // minimum: 1
  @JsonKey(name: r'release_version', required: true, includeIfNull: false)
  final int releaseVersion;

  @JsonKey(name: r'subject_name', required: true, includeIfNull: false)
  final String subjectName;

  // minimum: 1
  @JsonKey(name: r'grade_level_id', required: true, includeIfNull: false)
  final int gradeLevelId;

  // minimum: 1
  @JsonKey(name: r'session_year_id', required: true, includeIfNull: false)
  final int sessionYearId;

  // minimum: 1
  @JsonKey(name: r'term_id', required: true, includeIfNull: true)
  final int? termId;

  @JsonKey(name: r'mode', required: true, includeIfNull: false)
  final String mode;

  // minimum: 0
  @JsonKey(name: r'percentage_bp', required: true, includeIfNull: true)
  final int? percentageBp;

  // minimum: 0
  @JsonKey(name: r'rating_level', required: true, includeIfNull: true)
  final int? ratingLevel;

  // minimum: 0
  @JsonKey(name: r'coverage_bp', required: true, includeIfNull: true)
  final int? coverageBp;

  @JsonKey(name: r'fingerprint', required: true, includeIfNull: false)
  final String fingerprint;

  @JsonKey(name: r'released_at', required: true, includeIfNull: false)
  final DateTime releasedAt;

  @override
  bool operator ==(Object other) =>
      identical(this, other) ||
      other is TransferMark &&
          other.releaseId == releaseId &&
          other.releaseVersion == releaseVersion &&
          other.subjectName == subjectName &&
          other.gradeLevelId == gradeLevelId &&
          other.sessionYearId == sessionYearId &&
          other.termId == termId &&
          other.mode == mode &&
          other.percentageBp == percentageBp &&
          other.ratingLevel == ratingLevel &&
          other.coverageBp == coverageBp &&
          other.fingerprint == fingerprint &&
          other.releasedAt == releasedAt;

  @override
  int get hashCode =>
      releaseId.hashCode +
      releaseVersion.hashCode +
      subjectName.hashCode +
      gradeLevelId.hashCode +
      sessionYearId.hashCode +
      (termId == null ? 0 : termId.hashCode) +
      mode.hashCode +
      (percentageBp == null ? 0 : percentageBp.hashCode) +
      (ratingLevel == null ? 0 : ratingLevel.hashCode) +
      (coverageBp == null ? 0 : coverageBp.hashCode) +
      fingerprint.hashCode +
      releasedAt.hashCode;

  factory TransferMark.fromJson(Map<String, dynamic> json) =>
      _$TransferMarkFromJson(json);

  Map<String, dynamic> toJson() => _$TransferMarkToJson(this);

  @override
  String toString() {
    return toJson().toString();
  }
}
