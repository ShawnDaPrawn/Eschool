//
// AUTO-GENERATED FILE, DO NOT MODIFY!
//

// ignore_for_file: unused_element
import 'package:json_annotation/json_annotation.dart';

part 'child_mode_status.g.dart';

@JsonSerializable(
  checked: true,
  createToJson: true,
  disallowUnrecognizedKeys: false,
  explicitToJson: true,
)
class ChildModeStatus {
  /// Returns a new [ChildModeStatus] instance.
  ChildModeStatus({
    required this.learnerId,

    required this.firstName,

    required this.available,

    required this.reason,

    required this.message,

    required this.pinSet,

    required this.pinLockedUntil,

    required this.opens,

    required this.sessionMinutes,
  });

  @JsonKey(name: r'learner_id', required: true, includeIfNull: false)
  final int learnerId;

  @JsonKey(name: r'first_name', required: true, includeIfNull: false)
  final String firstName;

  @JsonKey(name: r'available', required: true, includeIfNull: false)
  final bool available;

  /// Why child mode is closed for this guardian and child; null when it is open.
  @JsonKey(
    name: r'reason',
    required: true,
    includeIfNull: true,
    unknownEnumValue: ChildModeStatusReasonEnum.unknownDefaultOpenApi,
  )
  final ChildModeStatusReasonEnum? reason;

  @JsonKey(name: r'message', required: true, includeIfNull: true)
  final String? message;

  @JsonKey(name: r'pin_set', required: true, includeIfNull: false)
  final bool pinSet;

  @JsonKey(name: r'pin_locked_until', required: true, includeIfNull: true)
  final DateTime? pinLockedUntil;

  /// What the child may open, in the order the app shows it.
  @JsonKey(
    name: r'opens',
    required: true,
    includeIfNull: false,
    unknownEnumValue: ChildModeStatusOpensEnum.unknownDefaultOpenApi,
  )
  final List<ChildModeStatusOpensEnum> opens;

  // minimum: 5
  // maximum: 120
  @JsonKey(name: r'session_minutes', required: true, includeIfNull: false)
  final int sessionMinutes;

  @override
  bool operator ==(Object other) =>
      identical(this, other) ||
      other is ChildModeStatus &&
          other.learnerId == learnerId &&
          other.firstName == firstName &&
          other.available == available &&
          other.reason == reason &&
          other.message == message &&
          other.pinSet == pinSet &&
          other.pinLockedUntil == pinLockedUntil &&
          other.opens == opens &&
          other.sessionMinutes == sessionMinutes;

  @override
  int get hashCode =>
      learnerId.hashCode +
      firstName.hashCode +
      available.hashCode +
      (reason == null ? 0 : reason.hashCode) +
      (message == null ? 0 : message.hashCode) +
      pinSet.hashCode +
      (pinLockedUntil == null ? 0 : pinLockedUntil.hashCode) +
      opens.hashCode +
      sessionMinutes.hashCode;

  factory ChildModeStatus.fromJson(Map<String, dynamic> json) =>
      _$ChildModeStatusFromJson(json);

  Map<String, dynamic> toJson() => _$ChildModeStatusToJson(this);

  @override
  String toString() {
    return toJson().toString();
  }
}

/// Why child mode is closed for this guardian and child; null when it is open.
enum ChildModeStatusReasonEnum {
  @JsonValue(r'not_approving')
  notApproving(r'not_approving'),
  @JsonValue(r'guardian_unverified')
  guardianUnverified(r'guardian_unverified'),
  @JsonValue(r'approval_needed')
  approvalNeeded(r'approval_needed'),
  @JsonValue(r'early_years')
  earlyYears(r'early_years'),
  @JsonValue(r'high_school')
  highSchool(r'high_school'),
  @JsonValue(r'grade_unknown')
  gradeUnknown(r'grade_unknown'),
  @JsonValue(r'not_member')
  notMember(r'not_member'),
  @JsonValue(r'unknown_default_open_api')
  unknownDefaultOpenApi(r'unknown_default_open_api');

  const ChildModeStatusReasonEnum(this.value);

  final String value;

  @override
  String toString() => value;
}

enum ChildModeStatusOpensEnum {
  @JsonValue(r'my_day')
  myDay(r'my_day'),
  @JsonValue(r'practice')
  practice(r'practice'),
  @JsonValue(r'tutor')
  tutor(r'tutor'),
  @JsonValue(r'library')
  library_(r'library'),
  @JsonValue(r'unknown_default_open_api')
  unknownDefaultOpenApi(r'unknown_default_open_api');

  const ChildModeStatusOpensEnum(this.value);

  final String value;

  @override
  String toString() => value;
}
