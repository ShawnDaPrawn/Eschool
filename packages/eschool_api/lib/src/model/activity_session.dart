//
// AUTO-GENERATED FILE, DO NOT MODIFY!
//

// ignore_for_file: unused_element
import 'package:json_annotation/json_annotation.dart';

part 'activity_session.g.dart';

@JsonSerializable(
  checked: true,
  createToJson: true,
  disallowUnrecognizedKeys: false,
  explicitToJson: true,
)
class ActivitySession {
  /// Returns a new [ActivitySession] instance.
  ActivitySession({
    required this.id,

    required this.activityGroupId,

    required this.onDate,

    required this.name,

    required this.venue,

    required this.status,

    required this.revision,
  });

  // minimum: 1
  @JsonKey(name: r'id', required: true, includeIfNull: false)
  final int id;

  // minimum: 1
  @JsonKey(name: r'activity_group_id', required: true, includeIfNull: false)
  final int activityGroupId;

  @JsonKey(name: r'on_date', required: true, includeIfNull: false)
  final String onDate;

  @JsonKey(name: r'name', required: true, includeIfNull: false)
  final String name;

  @JsonKey(name: r'venue', required: true, includeIfNull: false)
  final String venue;

  @JsonKey(
    name: r'status',
    required: true,
    includeIfNull: false,
    unknownEnumValue: ActivitySessionStatusEnum.unknownDefaultOpenApi,
  )
  final ActivitySessionStatusEnum status;

  // minimum: 1
  @JsonKey(name: r'revision', required: true, includeIfNull: false)
  final int revision;

  @override
  bool operator ==(Object other) =>
      identical(this, other) ||
      other is ActivitySession &&
          other.id == id &&
          other.activityGroupId == activityGroupId &&
          other.onDate == onDate &&
          other.name == name &&
          other.venue == venue &&
          other.status == status &&
          other.revision == revision;

  @override
  int get hashCode =>
      id.hashCode +
      activityGroupId.hashCode +
      onDate.hashCode +
      name.hashCode +
      venue.hashCode +
      status.hashCode +
      revision.hashCode;

  factory ActivitySession.fromJson(Map<String, dynamic> json) =>
      _$ActivitySessionFromJson(json);

  Map<String, dynamic> toJson() => _$ActivitySessionToJson(this);

  @override
  String toString() {
    return toJson().toString();
  }
}

enum ActivitySessionStatusEnum {
  @JsonValue(r'scheduled')
  scheduled(r'scheduled'),
  @JsonValue(r'completed')
  completed(r'completed'),
  @JsonValue(r'cancelled')
  cancelled(r'cancelled'),
  @JsonValue(r'unknown_default_open_api')
  unknownDefaultOpenApi(r'unknown_default_open_api');

  const ActivitySessionStatusEnum(this.value);

  final String value;

  @override
  String toString() => value;
}
