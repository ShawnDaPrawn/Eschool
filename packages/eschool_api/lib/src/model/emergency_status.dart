//
// AUTO-GENERATED FILE, DO NOT MODIFY!
//

// ignore_for_file: unused_element
import 'package:eschool_api/src/model/emergency_child_status.dart';
import 'package:eschool_api/src/model/emergency_public_alert.dart';
import 'package:json_annotation/json_annotation.dart';

part 'emergency_status.g.dart';

@JsonSerializable(
  checked: true,
  createToJson: true,
  disallowUnrecognizedKeys: false,
  explicitToJson: true,
)
class EmergencyStatus {
  /// Returns a new [EmergencyStatus] instance.
  EmergencyStatus({
    required this.alert,

    required this.children,

    required this.canAlert,

    required this.canRespond,

    required this.takesRollCall,
  });

  @JsonKey(name: r'alert', required: true, includeIfNull: true)
  final EmergencyPublicAlert? alert;

  @JsonKey(name: r'children', required: true, includeIfNull: false)
  final List<EmergencyChildStatus> children;

  @JsonKey(name: r'can_alert', required: true, includeIfNull: false)
  final bool canAlert;

  @JsonKey(name: r'can_respond', required: true, includeIfNull: false)
  final bool canRespond;

  @JsonKey(name: r'takes_roll_call', required: true, includeIfNull: false)
  final bool takesRollCall;

  @override
  bool operator ==(Object other) =>
      identical(this, other) ||
      other is EmergencyStatus &&
          other.alert == alert &&
          other.children == children &&
          other.canAlert == canAlert &&
          other.canRespond == canRespond &&
          other.takesRollCall == takesRollCall;

  @override
  int get hashCode =>
      (alert == null ? 0 : alert.hashCode) +
      children.hashCode +
      canAlert.hashCode +
      canRespond.hashCode +
      takesRollCall.hashCode;

  factory EmergencyStatus.fromJson(Map<String, dynamic> json) =>
      _$EmergencyStatusFromJson(json);

  Map<String, dynamic> toJson() => _$EmergencyStatusToJson(this);

  @override
  String toString() {
    return toJson().toString();
  }
}
