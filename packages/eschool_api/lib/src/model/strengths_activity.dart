//
// AUTO-GENERATED FILE, DO NOT MODIFY!
//

// ignore_for_file: unused_element
import 'package:json_annotation/json_annotation.dart';

part 'strengths_activity.g.dart';

@JsonSerializable(
  checked: true,
  createToJson: true,
  disallowUnrecognizedKeys: false,
  explicitToJson: true,
)
class StrengthsActivity {
  /// Returns a new [StrengthsActivity] instance.
  StrengthsActivity({
    required this.activityGroupId,

    required this.name,

    required this.discipline,

    required this.season,

    required this.role,
  });

  @JsonKey(name: r'activity_group_id', required: true, includeIfNull: false)
  final int activityGroupId;

  @JsonKey(name: r'name', required: true, includeIfNull: false)
  final String name;

  @JsonKey(name: r'discipline', required: true, includeIfNull: false)
  final String discipline;

  @JsonKey(name: r'season', required: true, includeIfNull: false)
  final String season;

  @JsonKey(
    name: r'role',
    required: true,
    includeIfNull: false,
    unknownEnumValue: StrengthsActivityRoleEnum.unknownDefaultOpenApi,
  )
  final StrengthsActivityRoleEnum role;

  @override
  bool operator ==(Object other) =>
      identical(this, other) ||
      other is StrengthsActivity &&
          other.activityGroupId == activityGroupId &&
          other.name == name &&
          other.discipline == discipline &&
          other.season == season &&
          other.role == role;

  @override
  int get hashCode =>
      activityGroupId.hashCode +
      name.hashCode +
      discipline.hashCode +
      season.hashCode +
      role.hashCode;

  factory StrengthsActivity.fromJson(Map<String, dynamic> json) =>
      _$StrengthsActivityFromJson(json);

  Map<String, dynamic> toJson() => _$StrengthsActivityToJson(this);

  @override
  String toString() {
    return toJson().toString();
  }
}

enum StrengthsActivityRoleEnum {
  @JsonValue(r'member')
  member(r'member'),
  @JsonValue(r'captain')
  captain(r'captain'),
  @JsonValue(r'unknown_default_open_api')
  unknownDefaultOpenApi(r'unknown_default_open_api');

  const StrengthsActivityRoleEnum(this.value);

  final String value;

  @override
  String toString() => value;
}
