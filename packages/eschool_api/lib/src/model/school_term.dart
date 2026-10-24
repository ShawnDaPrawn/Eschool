//
// AUTO-GENERATED FILE, DO NOT MODIFY!
//

// ignore_for_file: unused_element
import 'package:json_annotation/json_annotation.dart';

part 'school_term.g.dart';

@JsonSerializable(
  checked: true,
  createToJson: true,
  disallowUnrecognizedKeys: false,
  explicitToJson: true,
)
class SchoolTerm {
  /// Returns a new [SchoolTerm] instance.
  SchoolTerm({
    required this.id,

    required this.sessionYearId,

    required this.name,

    required this.startsOn,

    required this.endsOn,

    required this.gradeFrom,

    required this.gradeTo,

    required this.marksLockOn,

    required this.reportsOn,

    required this.current,

    required this.needsReview,
  });

  // minimum: 1
  @JsonKey(name: r'id', required: true, includeIfNull: false)
  final int id;

  // minimum: 1
  @JsonKey(name: r'session_year_id', required: true, includeIfNull: false)
  final int sessionYearId;

  @JsonKey(name: r'name', required: true, includeIfNull: false)
  final String name;

  @JsonKey(name: r'starts_on', required: true, includeIfNull: true)
  final DateTime? startsOn;

  @JsonKey(name: r'ends_on', required: true, includeIfNull: true)
  final DateTime? endsOn;

  // minimum: 0
  // maximum: 12
  @JsonKey(name: r'grade_from', required: true, includeIfNull: false)
  final int gradeFrom;

  // minimum: 0
  // maximum: 12
  @JsonKey(name: r'grade_to', required: true, includeIfNull: false)
  final int gradeTo;

  @JsonKey(name: r'marks_lock_on', required: true, includeIfNull: true)
  final DateTime? marksLockOn;

  @JsonKey(name: r'reports_on', required: true, includeIfNull: true)
  final DateTime? reportsOn;

  @JsonKey(name: r'current', required: true, includeIfNull: false)
  final bool current;

  @JsonKey(name: r'needs_review', required: true, includeIfNull: false)
  final bool needsReview;

  @override
  bool operator ==(Object other) =>
      identical(this, other) ||
      other is SchoolTerm &&
          other.id == id &&
          other.sessionYearId == sessionYearId &&
          other.name == name &&
          other.startsOn == startsOn &&
          other.endsOn == endsOn &&
          other.gradeFrom == gradeFrom &&
          other.gradeTo == gradeTo &&
          other.marksLockOn == marksLockOn &&
          other.reportsOn == reportsOn &&
          other.current == current &&
          other.needsReview == needsReview;

  @override
  int get hashCode =>
      id.hashCode +
      sessionYearId.hashCode +
      name.hashCode +
      (startsOn == null ? 0 : startsOn.hashCode) +
      (endsOn == null ? 0 : endsOn.hashCode) +
      gradeFrom.hashCode +
      gradeTo.hashCode +
      (marksLockOn == null ? 0 : marksLockOn.hashCode) +
      (reportsOn == null ? 0 : reportsOn.hashCode) +
      current.hashCode +
      needsReview.hashCode;

  factory SchoolTerm.fromJson(Map<String, dynamic> json) =>
      _$SchoolTermFromJson(json);

  Map<String, dynamic> toJson() => _$SchoolTermToJson(this);

  @override
  String toString() {
    return toJson().toString();
  }
}
