//
// AUTO-GENERATED FILE, DO NOT MODIFY!
//

// ignore_for_file: unused_element
import 'package:json_annotation/json_annotation.dart';

part 'academic_release_period.g.dart';

@JsonSerializable(
  checked: true,
  createToJson: true,
  disallowUnrecognizedKeys: false,
  explicitToJson: true,
)
class AcademicReleasePeriod {
  /// Returns a new [AcademicReleasePeriod] instance.
  AcademicReleasePeriod({
    required this.sessionYearId,

    required this.yearName,

    required this.termId,

    required this.termName,

    required this.releasedAt,
  });

  // minimum: 1
  @JsonKey(name: r'session_year_id', required: true, includeIfNull: false)
  final int sessionYearId;

  @JsonKey(name: r'year_name', required: true, includeIfNull: false)
  final String yearName;

  @JsonKey(name: r'term_id', required: true, includeIfNull: true)
  final int? termId;

  @JsonKey(name: r'term_name', required: true, includeIfNull: true)
  final String? termName;

  @JsonKey(name: r'released_at', required: true, includeIfNull: false)
  final String releasedAt;

  @override
  bool operator ==(Object other) =>
      identical(this, other) ||
      other is AcademicReleasePeriod &&
          other.sessionYearId == sessionYearId &&
          other.yearName == yearName &&
          other.termId == termId &&
          other.termName == termName &&
          other.releasedAt == releasedAt;

  @override
  int get hashCode =>
      sessionYearId.hashCode +
      yearName.hashCode +
      (termId == null ? 0 : termId.hashCode) +
      (termName == null ? 0 : termName.hashCode) +
      releasedAt.hashCode;

  factory AcademicReleasePeriod.fromJson(Map<String, dynamic> json) =>
      _$AcademicReleasePeriodFromJson(json);

  Map<String, dynamic> toJson() => _$AcademicReleasePeriodToJson(this);

  @override
  String toString() {
    return toJson().toString();
  }
}
