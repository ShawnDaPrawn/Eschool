//
// AUTO-GENERATED FILE, DO NOT MODIFY!
//

// ignore_for_file: unused_element
import 'package:json_annotation/json_annotation.dart';

part 'learner_passport_activity.g.dart';

@JsonSerializable(
  checked: true,
  createToJson: true,
  disallowUnrecognizedKeys: false,
  explicitToJson: true,
)
class LearnerPassportActivity {
  /// Returns a new [LearnerPassportActivity] instance.
  LearnerPassportActivity({
    required this.name,

    required this.discipline,

    required this.season,

    required this.schoolYear,

    required this.role,

    required this.startsOn,

    required this.endsOn,
  });

  @JsonKey(name: r'name', required: true, includeIfNull: false)
  final String name;

  @JsonKey(name: r'discipline', required: true, includeIfNull: false)
  final String discipline;

  @JsonKey(name: r'season', required: true, includeIfNull: false)
  final String season;

  @JsonKey(name: r'school_year', required: true, includeIfNull: true)
  final String? schoolYear;

  @JsonKey(
    name: r'role',
    required: true,
    includeIfNull: false,
    unknownEnumValue: LearnerPassportActivityRoleEnum.unknownDefaultOpenApi,
  )
  final LearnerPassportActivityRoleEnum role;

  @JsonKey(name: r'starts_on', required: true, includeIfNull: false)
  final DateTime startsOn;

  @JsonKey(name: r'ends_on', required: true, includeIfNull: false)
  final DateTime endsOn;

  @override
  bool operator ==(Object other) =>
      identical(this, other) ||
      other is LearnerPassportActivity &&
          other.name == name &&
          other.discipline == discipline &&
          other.season == season &&
          other.schoolYear == schoolYear &&
          other.role == role &&
          other.startsOn == startsOn &&
          other.endsOn == endsOn;

  @override
  int get hashCode =>
      name.hashCode +
      discipline.hashCode +
      season.hashCode +
      (schoolYear == null ? 0 : schoolYear.hashCode) +
      role.hashCode +
      startsOn.hashCode +
      endsOn.hashCode;

  factory LearnerPassportActivity.fromJson(Map<String, dynamic> json) =>
      _$LearnerPassportActivityFromJson(json);

  Map<String, dynamic> toJson() => _$LearnerPassportActivityToJson(this);

  @override
  String toString() {
    return toJson().toString();
  }
}

enum LearnerPassportActivityRoleEnum {
  @JsonValue(r'member')
  member(r'member'),
  @JsonValue(r'captain')
  captain(r'captain'),
  @JsonValue(r'unknown_default_open_api')
  unknownDefaultOpenApi(r'unknown_default_open_api');

  const LearnerPassportActivityRoleEnum(this.value);

  final String value;

  @override
  String toString() => value;
}
