//
// AUTO-GENERATED FILE, DO NOT MODIFY!
//

// ignore_for_file: unused_element
import 'package:json_annotation/json_annotation.dart';

part 'activity_staff_write.g.dart';

@JsonSerializable(
  checked: true,
  createToJson: true,
  disallowUnrecognizedKeys: false,
  explicitToJson: true,
)
class ActivityStaffWrite {
  /// Returns a new [ActivityStaffWrite] instance.
  ActivityStaffWrite({
    required this.operationId,

    required this.revision,

    required this.userId,

    required this.role,

    required this.cascade,

    this.active,

    required this.startsOn,

    required this.endsOn,

    required this.reason,
  });

  @JsonKey(name: r'operation_id', required: true, includeIfNull: false)
  final String operationId;

  // minimum: 0
  @JsonKey(name: r'revision', required: true, includeIfNull: false)
  final int revision;

  // minimum: 1
  @JsonKey(name: r'user_id', required: true, includeIfNull: false)
  final int userId;

  @JsonKey(
    name: r'role',
    required: true,
    includeIfNull: false,
    unknownEnumValue: ActivityStaffWriteRoleEnum.unknownDefaultOpenApi,
  )
  final ActivityStaffWriteRoleEnum role;

  @JsonKey(name: r'cascade', required: true, includeIfNull: false)
  final bool cascade;

  @JsonKey(name: r'active', required: false, includeIfNull: false)
  final bool? active;

  @JsonKey(name: r'starts_on', required: true, includeIfNull: false)
  final String startsOn;

  @JsonKey(name: r'ends_on', required: true, includeIfNull: false)
  final String endsOn;

  @JsonKey(name: r'reason', required: true, includeIfNull: false)
  final String reason;

  @override
  bool operator ==(Object other) =>
      identical(this, other) ||
      other is ActivityStaffWrite &&
          other.operationId == operationId &&
          other.revision == revision &&
          other.userId == userId &&
          other.role == role &&
          other.cascade == cascade &&
          other.active == active &&
          other.startsOn == startsOn &&
          other.endsOn == endsOn &&
          other.reason == reason;

  @override
  int get hashCode =>
      operationId.hashCode +
      revision.hashCode +
      userId.hashCode +
      role.hashCode +
      cascade.hashCode +
      active.hashCode +
      startsOn.hashCode +
      endsOn.hashCode +
      reason.hashCode;

  factory ActivityStaffWrite.fromJson(Map<String, dynamic> json) =>
      _$ActivityStaffWriteFromJson(json);

  Map<String, dynamic> toJson() => _$ActivityStaffWriteToJson(this);

  @override
  String toString() {
    return toJson().toString();
  }
}

enum ActivityStaffWriteRoleEnum {
  @JsonValue(r'admin')
  admin(r'admin'),
  @JsonValue(r'facilitator')
  facilitator(r'facilitator'),
  @JsonValue(r'unknown_default_open_api')
  unknownDefaultOpenApi(r'unknown_default_open_api');

  const ActivityStaffWriteRoleEnum(this.value);

  final String value;

  @override
  String toString() => value;
}
