//
// AUTO-GENERATED FILE, DO NOT MODIFY!
//

// ignore_for_file: unused_element
import 'package:eschool_api/src/model/mastery_settings.dart';
import 'package:eschool_api/src/model/mastery_group.dart';
import 'package:json_annotation/json_annotation.dart';

part 'mastery_configuration.g.dart';

@JsonSerializable(
  checked: true,
  createToJson: true,
  disallowUnrecognizedKeys: false,
  explicitToJson: true,
)
class MasteryConfiguration {
  /// Returns a new [MasteryConfiguration] instance.
  MasteryConfiguration({
    required this.sessionYearId,

    required this.canManage,

    required this.settings,

    required this.groups,
  });

  // minimum: 1
  @JsonKey(name: r'session_year_id', required: true, includeIfNull: false)
  final int sessionYearId;

  @JsonKey(name: r'can_manage', required: true, includeIfNull: false)
  final bool canManage;

  @JsonKey(name: r'settings', required: true, includeIfNull: false)
  final MasterySettings settings;

  @JsonKey(name: r'groups', required: true, includeIfNull: false)
  final List<MasteryGroup> groups;

  @override
  bool operator ==(Object other) =>
      identical(this, other) ||
      other is MasteryConfiguration &&
          other.sessionYearId == sessionYearId &&
          other.canManage == canManage &&
          other.settings == settings &&
          other.groups == groups;

  @override
  int get hashCode =>
      sessionYearId.hashCode +
      canManage.hashCode +
      settings.hashCode +
      groups.hashCode;

  factory MasteryConfiguration.fromJson(Map<String, dynamic> json) =>
      _$MasteryConfigurationFromJson(json);

  Map<String, dynamic> toJson() => _$MasteryConfigurationToJson(this);

  @override
  String toString() {
    return toJson().toString();
  }
}
