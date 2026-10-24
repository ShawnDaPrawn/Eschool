//
// AUTO-GENERATED FILE, DO NOT MODIFY!
//

// ignore_for_file: unused_element
import 'package:json_annotation/json_annotation.dart';

part 'school_term_write.g.dart';

@JsonSerializable(
  checked: true,
  createToJson: true,
  disallowUnrecognizedKeys: false,
  explicitToJson: true,
)
class SchoolTermWrite {
  /// Returns a new [SchoolTermWrite] instance.
  SchoolTermWrite({
    required this.sessionYearId,

    required this.name,

    required this.startsOn,

    required this.endsOn,

    this.gradeFrom,

    this.gradeTo,

    required this.marksLockOn,

    required this.reportsOn,
  });

  // minimum: 1
  @JsonKey(name: r'session_year_id', required: true, includeIfNull: false)
  final int sessionYearId;

  @JsonKey(name: r'name', required: true, includeIfNull: false)
  final String name;

  /// Local calendar date as YYYY-MM-DD, without a time or timezone.
  @JsonKey(name: r'starts_on', required: true, includeIfNull: false)
  final String startsOn;

  /// Local calendar date as YYYY-MM-DD, without a time or timezone.
  @JsonKey(name: r'ends_on', required: true, includeIfNull: false)
  final String endsOn;

  // minimum: 0
  // maximum: 12
  @JsonKey(name: r'grade_from', required: false, includeIfNull: false)
  final int? gradeFrom;

  // minimum: 0
  // maximum: 12
  @JsonKey(name: r'grade_to', required: false, includeIfNull: false)
  final int? gradeTo;

  /// Local calendar date as YYYY-MM-DD, without a time or timezone.
  @JsonKey(name: r'marks_lock_on', required: true, includeIfNull: true)
  final String? marksLockOn;

  /// Local calendar date as YYYY-MM-DD, without a time or timezone.
  @JsonKey(name: r'reports_on', required: true, includeIfNull: true)
  final String? reportsOn;

  @override
  bool operator ==(Object other) =>
      identical(this, other) ||
      other is SchoolTermWrite &&
          other.sessionYearId == sessionYearId &&
          other.name == name &&
          other.startsOn == startsOn &&
          other.endsOn == endsOn &&
          other.gradeFrom == gradeFrom &&
          other.gradeTo == gradeTo &&
          other.marksLockOn == marksLockOn &&
          other.reportsOn == reportsOn;

  @override
  int get hashCode =>
      sessionYearId.hashCode +
      name.hashCode +
      startsOn.hashCode +
      endsOn.hashCode +
      gradeFrom.hashCode +
      gradeTo.hashCode +
      (marksLockOn == null ? 0 : marksLockOn.hashCode) +
      (reportsOn == null ? 0 : reportsOn.hashCode);

  factory SchoolTermWrite.fromJson(Map<String, dynamic> json) =>
      _$SchoolTermWriteFromJson(json);

  Map<String, dynamic> toJson() => _$SchoolTermWriteToJson(this);

  @override
  String toString() {
    return toJson().toString();
  }
}
