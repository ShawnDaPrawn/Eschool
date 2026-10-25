//
// AUTO-GENERATED FILE, DO NOT MODIFY!
//

// ignore_for_file: unused_element
import 'package:json_annotation/json_annotation.dart';

part 'activity_group_staff.g.dart';

@JsonSerializable(
  checked: true,
  createToJson: true,
  disallowUnrecognizedKeys: false,
  explicitToJson: true,
)
class ActivityGroupStaff {
  /// Returns a new [ActivityGroupStaff] instance.
  ActivityGroupStaff({
    required this.id,

    required this.activityGroupId,

    required this.userId,

    required this.role,

    required this.cascade,

    required this.active,

    required this.startsOn,

    required this.endsOn,

    required this.reason,

    required this.revision,
  });

  // minimum: 1
  @JsonKey(name: r'id', required: true, includeIfNull: false)
  final int id;

  // minimum: 1
  @JsonKey(name: r'activity_group_id', required: true, includeIfNull: false)
  final int activityGroupId;

  // minimum: 1
  @JsonKey(name: r'user_id', required: true, includeIfNull: false)
  final int userId;

  @JsonKey(
    name: r'role',
    required: true,
    includeIfNull: false,
    unknownEnumValue: ActivityGroupStaffRoleEnum.unknownDefaultOpenApi,
  )
  final ActivityGroupStaffRoleEnum role;

  @JsonKey(name: r'cascade', required: true, includeIfNull: false)
  final bool cascade;

  @JsonKey(name: r'active', required: true, includeIfNull: false)
  final bool active;

  @JsonKey(name: r'starts_on', required: true, includeIfNull: false)
  final String startsOn;

  @JsonKey(name: r'ends_on', required: true, includeIfNull: false)
  final String endsOn;

  @JsonKey(name: r'reason', required: true, includeIfNull: false)
  final String reason;

  // minimum: 1
  @JsonKey(name: r'revision', required: true, includeIfNull: false)
  final int revision;

  @override
  bool operator ==(Object other) =>
      identical(this, other) ||
      other is ActivityGroupStaff &&
          other.id == id &&
          other.activityGroupId == activityGroupId &&
          other.userId == userId &&
          other.role == role &&
          other.cascade == cascade &&
          other.active == active &&
          other.startsOn == startsOn &&
          other.endsOn == endsOn &&
          other.reason == reason &&
          other.revision == revision;

  @override
  int get hashCode =>
      id.hashCode +
      activityGroupId.hashCode +
      userId.hashCode +
      role.hashCode +
      cascade.hashCode +
      active.hashCode +
      startsOn.hashCode +
      endsOn.hashCode +
      reason.hashCode +
      revision.hashCode;

  factory ActivityGroupStaff.fromJson(Map<String, dynamic> json) =>
      _$ActivityGroupStaffFromJson(json);

  Map<String, dynamic> toJson() => _$ActivityGroupStaffToJson(this);

  @override
  String toString() {
    return toJson().toString();
  }
}

enum ActivityGroupStaffRoleEnum {
  @JsonValue(r'admin')
  admin(r'admin'),
  @JsonValue(r'facilitator')
  facilitator(r'facilitator'),
  @JsonValue(r'unknown_default_open_api')
  unknownDefaultOpenApi(r'unknown_default_open_api');

  const ActivityGroupStaffRoleEnum(this.value);

  final String value;

  @override
  String toString() => value;
}
