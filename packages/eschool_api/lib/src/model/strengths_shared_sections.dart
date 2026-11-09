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

part 'strengths_shared_sections.g.dart';

@JsonSerializable(
  checked: true,
  createToJson: true,
  disallowUnrecognizedKeys: false,
  explicitToJson: true,
)
class StrengthsSharedSections {
  /// Returns a new [StrengthsSharedSections] instance.
  StrengthsSharedSections({
    this.growing,

    this.remembering,

    this.explored,

    this.activities,

    this.reading,

    this.mightEnjoy,
  });

  @JsonKey(name: r'growing', required: false, includeIfNull: false)
  final StrengthsTopicSection? growing;

  @JsonKey(name: r'remembering', required: false, includeIfNull: false)
  final StrengthsTopicSection? remembering;

  @JsonKey(name: r'explored', required: false, includeIfNull: false)
  final StrengthsExploredSection? explored;

  @JsonKey(name: r'activities', required: false, includeIfNull: false)
  final StrengthsActivitiesSection? activities;

  @JsonKey(name: r'reading', required: false, includeIfNull: false)
  final StrengthsReadingSection? reading;

  @JsonKey(name: r'might_enjoy', required: false, includeIfNull: false)
  final StrengthsIdeasSection? mightEnjoy;

  @override
  bool operator ==(Object other) =>
      identical(this, other) ||
      other is StrengthsSharedSections &&
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

  factory StrengthsSharedSections.fromJson(Map<String, dynamic> json) =>
      _$StrengthsSharedSectionsFromJson(json);

  Map<String, dynamic> toJson() => _$StrengthsSharedSectionsToJson(this);

  @override
  String toString() {
    return toJson().toString();
  }
}
