//
// AUTO-GENERATED FILE, DO NOT MODIFY!
//

// ignore_for_file: unused_element
import 'package:json_annotation/json_annotation.dart';

part 'weekly_digest_action_templates.g.dart';

@JsonSerializable(
  checked: true,
  createToJson: true,
  disallowUnrecognizedKeys: false,
  explicitToJson: true,
)
class WeeklyDigestActionTemplates {
  /// Returns a new [WeeklyDigestActionTemplates] instance.
  WeeklyDigestActionTemplates({
    required this.practice,

    required this.marks,

    required this.attendance,

    required this.calendar,
  });

  @JsonKey(name: r'practice', required: true, includeIfNull: false)
  final String practice;

  @JsonKey(name: r'marks', required: true, includeIfNull: false)
  final String marks;

  @JsonKey(name: r'attendance', required: true, includeIfNull: false)
  final String attendance;

  @JsonKey(name: r'calendar', required: true, includeIfNull: false)
  final String calendar;

  @override
  bool operator ==(Object other) =>
      identical(this, other) ||
      other is WeeklyDigestActionTemplates &&
          other.practice == practice &&
          other.marks == marks &&
          other.attendance == attendance &&
          other.calendar == calendar;

  @override
  int get hashCode =>
      practice.hashCode +
      marks.hashCode +
      attendance.hashCode +
      calendar.hashCode;

  factory WeeklyDigestActionTemplates.fromJson(Map<String, dynamic> json) =>
      _$WeeklyDigestActionTemplatesFromJson(json);

  Map<String, dynamic> toJson() => _$WeeklyDigestActionTemplatesToJson(this);

  @override
  String toString() {
    return toJson().toString();
  }
}
