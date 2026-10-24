//
// AUTO-GENERATED FILE, DO NOT MODIFY!
//

// ignore_for_file: unused_element
import 'package:json_annotation/json_annotation.dart';

part 'school_term_draft.g.dart';

@JsonSerializable(
  checked: true,
  createToJson: true,
  disallowUnrecognizedKeys: false,
  explicitToJson: true,
)
class SchoolTermDraft {
  /// Returns a new [SchoolTermDraft] instance.
  SchoolTermDraft({
    required this.name,

    required this.startsOn,

    required this.endsOn,

    required this.gradeFrom,

    required this.gradeTo,

    required this.marksLockOn,

    required this.reportsOn,
  });

  @JsonKey(name: r'name', required: true, includeIfNull: false)
  final String name;

  @JsonKey(name: r'starts_on', required: true, includeIfNull: false)
  final DateTime startsOn;

  @JsonKey(name: r'ends_on', required: true, includeIfNull: false)
  final DateTime endsOn;

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

  @override
  bool operator ==(Object other) =>
      identical(this, other) ||
      other is SchoolTermDraft &&
          other.name == name &&
          other.startsOn == startsOn &&
          other.endsOn == endsOn &&
          other.gradeFrom == gradeFrom &&
          other.gradeTo == gradeTo &&
          other.marksLockOn == marksLockOn &&
          other.reportsOn == reportsOn;

  @override
  int get hashCode =>
      name.hashCode +
      startsOn.hashCode +
      endsOn.hashCode +
      gradeFrom.hashCode +
      gradeTo.hashCode +
      (marksLockOn == null ? 0 : marksLockOn.hashCode) +
      (reportsOn == null ? 0 : reportsOn.hashCode);

  factory SchoolTermDraft.fromJson(Map<String, dynamic> json) =>
      _$SchoolTermDraftFromJson(json);

  Map<String, dynamic> toJson() => _$SchoolTermDraftToJson(this);

  @override
  String toString() {
    return toJson().toString();
  }
}
