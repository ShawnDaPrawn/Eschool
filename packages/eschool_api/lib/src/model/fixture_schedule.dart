//
// AUTO-GENERATED FILE, DO NOT MODIFY!
//

// ignore_for_file: unused_element
import 'package:json_annotation/json_annotation.dart';

part 'fixture_schedule.g.dart';

@JsonSerializable(
  checked: true,
  createToJson: true,
  disallowUnrecognizedKeys: false,
  explicitToJson: true,
)
class FixtureSchedule {
  /// Returns a new [FixtureSchedule] instance.
  FixtureSchedule({
    required this.operationId,

    required this.revision,

    required this.onDate,

    this.startsAt,

    required this.venue,

    this.field,

    required this.hostIsHome,

    required this.status,
  });

  @JsonKey(name: r'operation_id', required: true, includeIfNull: false)
  final String operationId;

  // minimum: 0
  @JsonKey(name: r'revision', required: true, includeIfNull: false)
  final int revision;

  @JsonKey(name: r'on_date', required: true, includeIfNull: false)
  final String onDate;

  @JsonKey(name: r'starts_at', required: false, includeIfNull: false)
  final String? startsAt;

  @JsonKey(name: r'venue', required: true, includeIfNull: false)
  final String venue;

  @JsonKey(name: r'field', required: false, includeIfNull: false)
  final String? field;

  @JsonKey(name: r'host_is_home', required: true, includeIfNull: false)
  final bool hostIsHome;

  @JsonKey(
    name: r'status',
    required: true,
    includeIfNull: false,
    unknownEnumValue: FixtureScheduleStatusEnum.unknownDefaultOpenApi,
  )
  final FixtureScheduleStatusEnum status;

  @override
  bool operator ==(Object other) =>
      identical(this, other) ||
      other is FixtureSchedule &&
          other.operationId == operationId &&
          other.revision == revision &&
          other.onDate == onDate &&
          other.startsAt == startsAt &&
          other.venue == venue &&
          other.field == field &&
          other.hostIsHome == hostIsHome &&
          other.status == status;

  @override
  int get hashCode =>
      operationId.hashCode +
      revision.hashCode +
      onDate.hashCode +
      (startsAt == null ? 0 : startsAt.hashCode) +
      venue.hashCode +
      (field == null ? 0 : field.hashCode) +
      hostIsHome.hashCode +
      status.hashCode;

  factory FixtureSchedule.fromJson(Map<String, dynamic> json) =>
      _$FixtureScheduleFromJson(json);

  Map<String, dynamic> toJson() => _$FixtureScheduleToJson(this);

  @override
  String toString() {
    return toJson().toString();
  }
}

enum FixtureScheduleStatusEnum {
  @JsonValue(r'scheduled')
  scheduled(r'scheduled'),
  @JsonValue(r'cancelled')
  cancelled(r'cancelled'),
  @JsonValue(r'unknown_default_open_api')
  unknownDefaultOpenApi(r'unknown_default_open_api');

  const FixtureScheduleStatusEnum(this.value);

  final String value;

  @override
  String toString() => value;
}
