//
// AUTO-GENERATED FILE, DO NOT MODIFY!
//

// ignore_for_file: unused_element
import 'package:json_annotation/json_annotation.dart';

part 'academic_release_data.g.dart';

@JsonSerializable(
  checked: true,
  createToJson: true,
  disallowUnrecognizedKeys: false,
  explicitToJson: true,
)
class AcademicReleaseData {
  /// Returns a new [AcademicReleaseData] instance.
  AcademicReleaseData({
    required this.id,

    required this.subjectId,

    required this.gradeLevelId,

    required this.sessionYearId,

    required this.termId,

    required this.mode,

    required this.provisional,

    required this.version,

    required this.fingerprint,

    required this.releasedAt,

    required this.learnerIds,
  });

  // minimum: 1
  @JsonKey(name: r'id', required: true, includeIfNull: false)
  final int id;

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

  // minimum: 1
  @JsonKey(name: r'version', required: true, includeIfNull: false)
  final int version;

  @JsonKey(name: r'fingerprint', required: true, includeIfNull: false)
  final String fingerprint;

  @JsonKey(name: r'released_at', required: true, includeIfNull: false)
  final String releasedAt;

  @JsonKey(name: r'learner_ids', required: true, includeIfNull: false)
  final List<int> learnerIds;

  @override
  bool operator ==(Object other) =>
      identical(this, other) ||
      other is AcademicReleaseData &&
          other.id == id &&
          other.subjectId == subjectId &&
          other.gradeLevelId == gradeLevelId &&
          other.sessionYearId == sessionYearId &&
          other.termId == termId &&
          other.mode == mode &&
          other.provisional == provisional &&
          other.version == version &&
          other.fingerprint == fingerprint &&
          other.releasedAt == releasedAt &&
          other.learnerIds == learnerIds;

  @override
  int get hashCode =>
      id.hashCode +
      subjectId.hashCode +
      gradeLevelId.hashCode +
      sessionYearId.hashCode +
      (termId == null ? 0 : termId.hashCode) +
      mode.hashCode +
      provisional.hashCode +
      version.hashCode +
      fingerprint.hashCode +
      releasedAt.hashCode +
      learnerIds.hashCode;

  factory AcademicReleaseData.fromJson(Map<String, dynamic> json) =>
      _$AcademicReleaseDataFromJson(json);

  Map<String, dynamic> toJson() => _$AcademicReleaseDataToJson(this);

  @override
  String toString() {
    return toJson().toString();
  }
}
