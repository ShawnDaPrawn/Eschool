//
// AUTO-GENERATED FILE, DO NOT MODIFY!
//

// ignore_for_file: unused_element
import 'package:json_annotation/json_annotation.dart';

part 'decide_guardian_approval_request.g.dart';

@JsonSerializable(
  checked: true,
  createToJson: true,
  disallowUnrecognizedKeys: false,
  explicitToJson: true,
)
class DecideGuardianApprovalRequest {
  /// Returns a new [DecideGuardianApprovalRequest] instance.
  DecideGuardianApprovalRequest({
    required this.decision,

    required this.consentVersion,
  });

  @JsonKey(
    name: r'decision',
    required: true,
    includeIfNull: false,
    unknownEnumValue:
        DecideGuardianApprovalRequestDecisionEnum.unknownDefaultOpenApi,
  )
  final DecideGuardianApprovalRequestDecisionEnum decision;

  @JsonKey(name: r'consent_version', required: true, includeIfNull: false)
  final String consentVersion;

  @override
  bool operator ==(Object other) =>
      identical(this, other) ||
      other is DecideGuardianApprovalRequest &&
          other.decision == decision &&
          other.consentVersion == consentVersion;

  @override
  int get hashCode => decision.hashCode + consentVersion.hashCode;

  factory DecideGuardianApprovalRequest.fromJson(Map<String, dynamic> json) =>
      _$DecideGuardianApprovalRequestFromJson(json);

  Map<String, dynamic> toJson() => _$DecideGuardianApprovalRequestToJson(this);

  @override
  String toString() {
    return toJson().toString();
  }
}

enum DecideGuardianApprovalRequestDecisionEnum {
  @JsonValue(r'approve')
  approve(r'approve'),
  @JsonValue(r'withdraw')
  withdraw(r'withdraw'),
  @JsonValue(r'unknown_default_open_api')
  unknownDefaultOpenApi(r'unknown_default_open_api');

  const DecideGuardianApprovalRequestDecisionEnum(this.value);

  final String value;

  @override
  String toString() => value;
}
