//
// AUTO-GENERATED FILE, DO NOT MODIFY!
//

// ignore_for_file: unused_element
import 'package:json_annotation/json_annotation.dart';

part 'activity_group.g.dart';

@JsonSerializable(
  checked: true,
  createToJson: true,
  disallowUnrecognizedKeys: false,
  explicitToJson: true,
)
class ActivityGroup {
  /// Returns a new [ActivityGroup] instance.
  ActivityGroup({
    required this.id,

    required this.schoolId,

    required this.sessionYearId,

    required this.parentId,

    required this.name,

    required this.discipline,

    required this.season,

    required this.startsOn,

    required this.endsOn,

    required this.gender,

    required this.minimumGrade,

    required this.maximumGrade,

    required this.minimumAge,

    required this.maximumAge,

    required this.active,

    required this.revision,
  });

  // minimum: 1
  @JsonKey(name: r'id', required: true, includeIfNull: false)
  final int id;

  // minimum: 1
  @JsonKey(name: r'school_id', required: true, includeIfNull: false)
  final int schoolId;

  // minimum: 1
  @JsonKey(name: r'session_year_id', required: true, includeIfNull: false)
  final int sessionYearId;

  // minimum: 1
  @JsonKey(name: r'parent_id', required: true, includeIfNull: true)
  final int? parentId;

  @JsonKey(name: r'name', required: true, includeIfNull: false)
  final String name;

  @JsonKey(name: r'discipline', required: true, includeIfNull: false)
  final String discipline;

  @JsonKey(name: r'season', required: true, includeIfNull: false)
  final String season;

  @JsonKey(name: r'starts_on', required: true, includeIfNull: false)
  final String startsOn;

  @JsonKey(name: r'ends_on', required: true, includeIfNull: false)
  final String endsOn;

  @JsonKey(
    name: r'gender',
    required: true,
    includeIfNull: false,
    unknownEnumValue: ActivityGroupGenderEnum.unknownDefaultOpenApi,
  )
  final ActivityGroupGenderEnum gender;

  // minimum: 0
  // maximum: 12
  @JsonKey(name: r'minimum_grade', required: true, includeIfNull: true)
  final int? minimumGrade;

  // minimum: 0
  // maximum: 12
  @JsonKey(name: r'maximum_grade', required: true, includeIfNull: true)
  final int? maximumGrade;

  // minimum: 0
  // maximum: 25
  @JsonKey(name: r'minimum_age', required: true, includeIfNull: true)
  final int? minimumAge;

  // minimum: 0
  // maximum: 25
  @JsonKey(name: r'maximum_age', required: true, includeIfNull: true)
  final int? maximumAge;

  @JsonKey(name: r'active', required: true, includeIfNull: false)
  final bool active;

  // minimum: 1
  @JsonKey(name: r'revision', required: true, includeIfNull: false)
  final int revision;

  @override
  bool operator ==(Object other) =>
      identical(this, other) ||
      other is ActivityGroup &&
          other.id == id &&
          other.schoolId == schoolId &&
          other.sessionYearId == sessionYearId &&
          other.parentId == parentId &&
          other.name == name &&
          other.discipline == discipline &&
          other.season == season &&
          other.startsOn == startsOn &&
          other.endsOn == endsOn &&
          other.gender == gender &&
          other.minimumGrade == minimumGrade &&
          other.maximumGrade == maximumGrade &&
          other.minimumAge == minimumAge &&
          other.maximumAge == maximumAge &&
          other.active == active &&
          other.revision == revision;

  @override
  int get hashCode =>
      id.hashCode +
      schoolId.hashCode +
      sessionYearId.hashCode +
      (parentId == null ? 0 : parentId.hashCode) +
      name.hashCode +
      discipline.hashCode +
      season.hashCode +
      startsOn.hashCode +
      endsOn.hashCode +
      gender.hashCode +
      (minimumGrade == null ? 0 : minimumGrade.hashCode) +
      (maximumGrade == null ? 0 : maximumGrade.hashCode) +
      (minimumAge == null ? 0 : minimumAge.hashCode) +
      (maximumAge == null ? 0 : maximumAge.hashCode) +
      active.hashCode +
      revision.hashCode;

  factory ActivityGroup.fromJson(Map<String, dynamic> json) =>
      _$ActivityGroupFromJson(json);

  Map<String, dynamic> toJson() => _$ActivityGroupToJson(this);

  @override
  String toString() {
    return toJson().toString();
  }
}

enum ActivityGroupGenderEnum {
  @JsonValue(r'any')
  any(r'any'),
  @JsonValue(r'male')
  male(r'male'),
  @JsonValue(r'female')
  female(r'female'),
  @JsonValue(r'unknown_default_open_api')
  unknownDefaultOpenApi(r'unknown_default_open_api');

  const ActivityGroupGenderEnum(this.value);

  final String value;

  @override
  String toString() => value;
}
