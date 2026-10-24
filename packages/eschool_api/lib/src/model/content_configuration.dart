//
// AUTO-GENERATED FILE, DO NOT MODIFY!
//

// ignore_for_file: unused_element
import 'package:eschool_api/src/model/content_year.dart';
import 'package:eschool_api/src/model/content_settings.dart';
import 'package:eschool_api/src/model/content_topic_choice.dart';
import 'package:eschool_api/src/model/content_group.dart';
import 'package:json_annotation/json_annotation.dart';

part 'content_configuration.g.dart';

@JsonSerializable(
  checked: true,
  createToJson: true,
  disallowUnrecognizedKeys: false,
  explicitToJson: true,
)
class ContentConfiguration {
  /// Returns a new [ContentConfiguration] instance.
  ContentConfiguration({
    required this.sessionYearId,

    required this.schoolYears,

    required this.groups,

    required this.topics,

    required this.settings,

    required this.canManage,

    required this.maximumSourceBytes,
  });

  // minimum: 1
  @JsonKey(name: r'session_year_id', required: true, includeIfNull: false)
  final int sessionYearId;

  @JsonKey(name: r'school_years', required: true, includeIfNull: false)
  final List<ContentYear> schoolYears;

  @JsonKey(name: r'groups', required: true, includeIfNull: false)
  final List<ContentGroup> groups;

  @JsonKey(name: r'topics', required: true, includeIfNull: false)
  final List<ContentTopicChoice> topics;

  @JsonKey(name: r'settings', required: true, includeIfNull: false)
  final ContentSettings settings;

  @JsonKey(name: r'can_manage', required: true, includeIfNull: false)
  final bool canManage;

  @JsonKey(name: r'maximum_source_bytes', required: true, includeIfNull: false)
  final int maximumSourceBytes;

  @override
  bool operator ==(Object other) =>
      identical(this, other) ||
      other is ContentConfiguration &&
          other.sessionYearId == sessionYearId &&
          other.schoolYears == schoolYears &&
          other.groups == groups &&
          other.topics == topics &&
          other.settings == settings &&
          other.canManage == canManage &&
          other.maximumSourceBytes == maximumSourceBytes;

  @override
  int get hashCode =>
      sessionYearId.hashCode +
      schoolYears.hashCode +
      groups.hashCode +
      topics.hashCode +
      settings.hashCode +
      canManage.hashCode +
      maximumSourceBytes.hashCode;

  factory ContentConfiguration.fromJson(Map<String, dynamic> json) =>
      _$ContentConfigurationFromJson(json);

  Map<String, dynamic> toJson() => _$ContentConfigurationToJson(this);

  @override
  String toString() {
    return toJson().toString();
  }
}
