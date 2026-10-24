//
// AUTO-GENERATED FILE, DO NOT MODIFY!
//

// ignore_for_file: unused_element
import 'package:json_annotation/json_annotation.dart';

part 'academic_transition_retention.g.dart';

@JsonSerializable(
  checked: true,
  createToJson: true,
  disallowUnrecognizedKeys: false,
  explicitToJson: true,
)
class AcademicTransitionRetention {
  /// Returns a new [AcademicTransitionRetention] instance.
  AcademicTransitionRetention({
    required this.days,

    required this.maximumDays,

    required this.revision,

    required this.educationalRecords,
  });

  // minimum: 1
  // maximum: 30
  @JsonKey(name: r'days', required: true, includeIfNull: false)
  final int days;

  @JsonKey(name: r'maximum_days', required: true, includeIfNull: false)
  final int maximumDays;

  // minimum: 0
  @JsonKey(name: r'revision', required: true, includeIfNull: false)
  final int revision;

  @JsonKey(name: r'educational_records', required: true, includeIfNull: false)
  final String educationalRecords;

  @override
  bool operator ==(Object other) =>
      identical(this, other) ||
      other is AcademicTransitionRetention &&
          other.days == days &&
          other.maximumDays == maximumDays &&
          other.revision == revision &&
          other.educationalRecords == educationalRecords;

  @override
  int get hashCode =>
      days.hashCode +
      maximumDays.hashCode +
      revision.hashCode +
      educationalRecords.hashCode;

  factory AcademicTransitionRetention.fromJson(Map<String, dynamic> json) =>
      _$AcademicTransitionRetentionFromJson(json);

  Map<String, dynamic> toJson() => _$AcademicTransitionRetentionToJson(this);

  @override
  String toString() {
    return toJson().toString();
  }
}
