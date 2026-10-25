//
// AUTO-GENERATED FILE, DO NOT MODIFY!
//

// ignore_for_file: unused_element
import 'package:json_annotation/json_annotation.dart';

part 'activity_membership_write.g.dart';

@JsonSerializable(
  checked: true,
  createToJson: true,
  disallowUnrecognizedKeys: false,
  explicitToJson: true,
)
class ActivityMembershipWrite {
  /// Returns a new [ActivityMembershipWrite] instance.
  ActivityMembershipWrite({
    required this.operationId,

    required this.revision,

    required this.learnerId,

    required this.role,

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
  @JsonKey(name: r'learner_id', required: true, includeIfNull: false)
  final int learnerId;

  @JsonKey(
    name: r'role',
    required: true,
    includeIfNull: false,
    unknownEnumValue: ActivityMembershipWriteRoleEnum.unknownDefaultOpenApi,
  )
  final ActivityMembershipWriteRoleEnum role;

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
      other is ActivityMembershipWrite &&
          other.operationId == operationId &&
          other.revision == revision &&
          other.learnerId == learnerId &&
          other.role == role &&
          other.active == active &&
          other.startsOn == startsOn &&
          other.endsOn == endsOn &&
          other.reason == reason;

  @override
  int get hashCode =>
      operationId.hashCode +
      revision.hashCode +
      learnerId.hashCode +
      role.hashCode +
      active.hashCode +
      startsOn.hashCode +
      endsOn.hashCode +
      reason.hashCode;

  factory ActivityMembershipWrite.fromJson(Map<String, dynamic> json) =>
      _$ActivityMembershipWriteFromJson(json);

  Map<String, dynamic> toJson() => _$ActivityMembershipWriteToJson(this);

  @override
  String toString() {
    return toJson().toString();
  }
}

enum ActivityMembershipWriteRoleEnum {
  @JsonValue(r'member')
  member(r'member'),
  @JsonValue(r'captain')
  captain(r'captain'),
  @JsonValue(r'unknown_default_open_api')
  unknownDefaultOpenApi(r'unknown_default_open_api');

  const ActivityMembershipWriteRoleEnum(this.value);

  final String value;

  @override
  String toString() => value;
}
