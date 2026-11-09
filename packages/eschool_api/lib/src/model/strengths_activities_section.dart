//
// AUTO-GENERATED FILE, DO NOT MODIFY!
//

// ignore_for_file: unused_element
import 'package:eschool_api/src/model/strengths_skill.dart';
import 'package:eschool_api/src/model/strengths_activity.dart';
import 'package:json_annotation/json_annotation.dart';

part 'strengths_activities_section.g.dart';

@JsonSerializable(
  checked: true,
  createToJson: true,
  disallowUnrecognizedKeys: false,
  explicitToJson: true,
)
class StrengthsActivitiesSection {
  /// Returns a new [StrengthsActivitiesSection] instance.
  StrengthsActivitiesSection({
    required this.available,

    required this.title,

    required this.message,

    required this.items,

    required this.skills,
  });

  @JsonKey(name: r'available', required: true, includeIfNull: false)
  final bool available;

  @JsonKey(name: r'title', required: true, includeIfNull: false)
  final String title;

  @JsonKey(name: r'message', required: true, includeIfNull: false)
  final String message;

  @JsonKey(name: r'items', required: true, includeIfNull: false)
  final List<StrengthsActivity> items;

  /// Leading others (a captaincy) and helping others learn (completed supervised peer tutoring).
  @JsonKey(name: r'skills', required: true, includeIfNull: false)
  final List<StrengthsSkill> skills;

  @override
  bool operator ==(Object other) =>
      identical(this, other) ||
      other is StrengthsActivitiesSection &&
          other.available == available &&
          other.title == title &&
          other.message == message &&
          other.items == items &&
          other.skills == skills;

  @override
  int get hashCode =>
      available.hashCode +
      title.hashCode +
      message.hashCode +
      items.hashCode +
      skills.hashCode;

  factory StrengthsActivitiesSection.fromJson(Map<String, dynamic> json) =>
      _$StrengthsActivitiesSectionFromJson(json);

  Map<String, dynamic> toJson() => _$StrengthsActivitiesSectionToJson(this);

  @override
  String toString() {
    return toJson().toString();
  }
}
