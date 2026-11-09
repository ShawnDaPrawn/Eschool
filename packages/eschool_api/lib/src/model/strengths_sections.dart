//
// AUTO-GENERATED FILE, DO NOT MODIFY!
//

// ignore_for_file: unused_element
import 'package:eschool_api/src/model/strengths_activities_section.dart';
import 'package:eschool_api/src/model/strengths_ideas_section.dart';
import 'package:eschool_api/src/model/strengths_reading_section.dart';
import 'package:eschool_api/src/model/strengths_topic_section.dart';
import 'package:eschool_api/src/model/strengths_explored_section.dart';
import 'package:json_annotation/json_annotation.dart';

part 'strengths_sections.g.dart';

@JsonSerializable(
  checked: true,
  createToJson: true,
  disallowUnrecognizedKeys: false,
  explicitToJson: true,
)
class StrengthsSections {
  /// Returns a new [StrengthsSections] instance.
  StrengthsSections({
    required this.growing,

    required this.remembering,

    required this.explored,

    required this.activities,

    required this.reading,

    required this.mightEnjoy,
  });

  @JsonKey(name: r'growing', required: true, includeIfNull: false)
  final StrengthsTopicSection growing;

  @JsonKey(name: r'remembering', required: true, includeIfNull: false)
  final StrengthsTopicSection remembering;

  @JsonKey(name: r'explored', required: true, includeIfNull: false)
  final StrengthsExploredSection explored;

  @JsonKey(name: r'activities', required: true, includeIfNull: false)
  final StrengthsActivitiesSection activities;

  @JsonKey(name: r'reading', required: true, includeIfNull: false)
  final StrengthsReadingSection reading;

  @JsonKey(name: r'might_enjoy', required: true, includeIfNull: false)
  final StrengthsIdeasSection mightEnjoy;

  @override
  bool operator ==(Object other) =>
      identical(this, other) ||
      other is StrengthsSections &&
          other.growing == growing &&
          other.remembering == remembering &&
          other.explored == explored &&
          other.activities == activities &&
          other.reading == reading &&
          other.mightEnjoy == mightEnjoy;

  @override
  int get hashCode =>
      growing.hashCode +
      remembering.hashCode +
      explored.hashCode +
      activities.hashCode +
      reading.hashCode +
      mightEnjoy.hashCode;

  factory StrengthsSections.fromJson(Map<String, dynamic> json) =>
      _$StrengthsSectionsFromJson(json);

  Map<String, dynamic> toJson() => _$StrengthsSectionsToJson(this);

  @override
  String toString() {
    return toJson().toString();
  }
}
