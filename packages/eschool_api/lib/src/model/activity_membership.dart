//
// AUTO-GENERATED FILE, DO NOT MODIFY!
//

// ignore_for_file: unused_element
import 'package:json_annotation/json_annotation.dart';

part 'activity_membership.g.dart';

@JsonSerializable(
  checked: true,
  createToJson: true,
  disallowUnrecognizedKeys: false,
  explicitToJson: true,
)
class ActivityMembership {
  /// Returns a new [ActivityMembership] instance.
  ActivityMembership({
    required this.id,

    required this.activityGroupId,

    required this.learnerId,

    required this.role,

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
  @JsonKey(name: r'learner_id', required: true, includeIfNull: false)
  final int learnerId;

  @JsonKey(
    name: r'role',
    required: true,
    includeIfNull: false,
    unknownEnumValue: ActivityMembershipRoleEnum.unknownDefaultOpenApi,
  )
  final ActivityMembershipRoleEnum role;

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
      other is ActivityMembership &&
          other.id == id &&
          other.activityGroupId == activityGroupId &&
          other.learnerId == learnerId &&
          other.role == role &&
          other.active == active &&
          other.startsOn == startsOn &&
          other.endsOn == endsOn &&
          other.reason == reason &&
          other.revision == revision;

  @override
  int get hashCode =>
      id.hashCode +
      activityGroupId.hashCode +
      learnerId.hashCode +
      role.hashCode +
      active.hashCode +
      startsOn.hashCode +
      endsOn.hashCode +
      reason.hashCode +
      revision.hashCode;

  factory ActivityMembership.fromJson(Map<String, dynamic> json) =>
      _$ActivityMembershipFromJson(json);

  Map<String, dynamic> toJson() => _$ActivityMembershipToJson(this);

  @override
  String toString() {
    return toJson().toString();
  }
}

enum ActivityMembershipRoleEnum {
  @JsonValue(r'member')
  member(r'member'),
  @JsonValue(r'captain')
  captain(r'captain'),
  @JsonValue(r'unknown_default_open_api')
  unknownDefaultOpenApi(r'unknown_default_open_api');

  const ActivityMembershipRoleEnum(this.value);

  final String value;

  @override
  String toString() => value;
}
