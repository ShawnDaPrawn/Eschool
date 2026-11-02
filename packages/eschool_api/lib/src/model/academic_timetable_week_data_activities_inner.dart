//
// AUTO-GENERATED FILE, DO NOT MODIFY!
//

// ignore_for_file: unused_element
import 'package:json_annotation/json_annotation.dart';

part 'academic_timetable_week_data_activities_inner.g.dart';

@JsonSerializable(
  checked: true,
  createToJson: true,
  disallowUnrecognizedKeys: false,
  explicitToJson: true,
)
class AcademicTimetableWeekDataActivitiesInner {
  /// Returns a new [AcademicTimetableWeekDataActivitiesInner] instance.
  AcademicTimetableWeekDataActivitiesInner({
    required this.id,

    required this.date,

    required this.startsAt,

    required this.endsAt,

    required this.name,

    required this.groupName,

    required this.venue,

    required this.status,
  });

  // minimum: 1
  @JsonKey(name: r'id', required: true, includeIfNull: false)
  final int id;

  @JsonKey(name: r'date', required: true, includeIfNull: false)
  final String date;

  @JsonKey(name: r'starts_at', required: true, includeIfNull: true)
  final String? startsAt;

  @JsonKey(name: r'ends_at', required: true, includeIfNull: true)
  final String? endsAt;

  @JsonKey(name: r'name', required: true, includeIfNull: false)
  final String name;

  @JsonKey(name: r'group_name', required: true, includeIfNull: false)
  final String groupName;

  @JsonKey(name: r'venue', required: true, includeIfNull: false)
  final String venue;

  @JsonKey(
    name: r'status',
    required: true,
    includeIfNull: false,
    unknownEnumValue: AcademicTimetableWeekDataActivitiesInnerStatusEnum
        .unknownDefaultOpenApi,
  )
  final AcademicTimetableWeekDataActivitiesInnerStatusEnum status;

  @override
  bool operator ==(Object other) =>
      identical(this, other) ||
      other is AcademicTimetableWeekDataActivitiesInner &&
          other.id == id &&
          other.date == date &&
          other.startsAt == startsAt &&
          other.endsAt == endsAt &&
          other.name == name &&
          other.groupName == groupName &&
          other.venue == venue &&
          other.status == status;

  @override
  int get hashCode =>
      id.hashCode +
      date.hashCode +
      (startsAt == null ? 0 : startsAt.hashCode) +
      (endsAt == null ? 0 : endsAt.hashCode) +
      name.hashCode +
      groupName.hashCode +
      venue.hashCode +
      status.hashCode;

  factory AcademicTimetableWeekDataActivitiesInner.fromJson(
    Map<String, dynamic> json,
  ) => _$AcademicTimetableWeekDataActivitiesInnerFromJson(json);

  Map<String, dynamic> toJson() =>
      _$AcademicTimetableWeekDataActivitiesInnerToJson(this);

  @override
  String toString() {
    return toJson().toString();
  }
}

enum AcademicTimetableWeekDataActivitiesInnerStatusEnum {
  @JsonValue(r'scheduled')
  scheduled(r'scheduled'),
  @JsonValue(r'completed')
  completed(r'completed'),
  @JsonValue(r'unknown_default_open_api')
  unknownDefaultOpenApi(r'unknown_default_open_api');

  const AcademicTimetableWeekDataActivitiesInnerStatusEnum(this.value);

  final String value;

  @override
  String toString() => value;
}
