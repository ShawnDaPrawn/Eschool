//
// AUTO-GENERATED FILE, DO NOT MODIFY!
//

// ignore_for_file: unused_element
import 'package:json_annotation/json_annotation.dart';

part 'activity_group_write.g.dart';

@JsonSerializable(
  checked: true,
  createToJson: true,
  disallowUnrecognizedKeys: false,
  explicitToJson: true,
)
class ActivityGroupWrite {
  /// Returns a new [ActivityGroupWrite] instance.
  ActivityGroupWrite({
    required this.operationId,

    required this.revision,

    required this.sessionYearId,

    this.parentId,

    required this.name,

    required this.discipline,

    required this.season,

    required this.startsOn,

    required this.endsOn,

    required this.gender,

    this.minimumGrade,

    this.maximumGrade,

    this.minimumAge,

    this.maximumAge,

    required this.active,
  });

  @JsonKey(name: r'operation_id', required: true, includeIfNull: false)
  final String operationId;

  // minimum: 0
  @JsonKey(name: r'revision', required: true, includeIfNull: false)
  final int revision;

  // minimum: 1
  @JsonKey(name: r'session_year_id', required: true, includeIfNull: false)
  final int sessionYearId;

  // minimum: 1
  @JsonKey(name: r'parent_id', required: false, includeIfNull: false)
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
    unknownEnumValue: ActivityGroupWriteGenderEnum.unknownDefaultOpenApi,
  )
  final ActivityGroupWriteGenderEnum gender;

  // minimum: 0
  // maximum: 12
  @JsonKey(name: r'minimum_grade', required: false, includeIfNull: false)
  final int? minimumGrade;

  // minimum: 0
  // maximum: 12
  @JsonKey(name: r'maximum_grade', required: false, includeIfNull: false)
  final int? maximumGrade;

  // minimum: 0
  // maximum: 25
  @JsonKey(name: r'minimum_age', required: false, includeIfNull: false)
  final int? minimumAge;

  // minimum: 0
  // maximum: 25
  @JsonKey(name: r'maximum_age', required: false, includeIfNull: false)
  final int? maximumAge;

  @JsonKey(name: r'active', required: true, includeIfNull: false)
  final bool active;

  @override
  bool operator ==(Object other) =>
      identical(this, other) ||
      other is ActivityGroupWrite &&
          other.operationId == operationId &&
          other.revision == revision &&
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
          other.active == active;

  @override
  int get hashCode =>
      operationId.hashCode +
      revision.hashCode +
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
      active.hashCode;

  factory ActivityGroupWrite.fromJson(Map<String, dynamic> json) =>
      _$ActivityGroupWriteFromJson(json);

  Map<String, dynamic> toJson() => _$ActivityGroupWriteToJson(this);

  @override
  String toString() {
    return toJson().toString();
  }
}

enum ActivityGroupWriteGenderEnum {
  @JsonValue(r'any')
  any(r'any'),
  @JsonValue(r'male')
  male(r'male'),
  @JsonValue(r'female')
  female(r'female'),
  @JsonValue(r'unknown_default_open_api')
  unknownDefaultOpenApi(r'unknown_default_open_api');

  const ActivityGroupWriteGenderEnum(this.value);

  final String value;

  @override
  String toString() => value;
}
