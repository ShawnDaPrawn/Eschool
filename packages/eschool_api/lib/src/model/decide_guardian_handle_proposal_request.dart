//
// AUTO-GENERATED FILE, DO NOT MODIFY!
//

// ignore_for_file: unused_element
import 'package:json_annotation/json_annotation.dart';

part 'decide_guardian_handle_proposal_request.g.dart';

@JsonSerializable(
  checked: true,
  createToJson: true,
  disallowUnrecognizedKeys: false,
  explicitToJson: true,
)
class DecideGuardianHandleProposalRequest {
  /// Returns a new [DecideGuardianHandleProposalRequest] instance.
  DecideGuardianHandleProposalRequest({required this.decision});

  @JsonKey(
    name: r'decision',
    required: true,
    includeIfNull: false,
    unknownEnumValue:
        DecideGuardianHandleProposalRequestDecisionEnum.unknownDefaultOpenApi,
  )
  final DecideGuardianHandleProposalRequestDecisionEnum decision;

  @override
  bool operator ==(Object other) =>
      identical(this, other) ||
      other is DecideGuardianHandleProposalRequest &&
          other.decision == decision;

  @override
  int get hashCode => decision.hashCode;

  factory DecideGuardianHandleProposalRequest.fromJson(
    Map<String, dynamic> json,
  ) => _$DecideGuardianHandleProposalRequestFromJson(json);

  Map<String, dynamic> toJson() =>
      _$DecideGuardianHandleProposalRequestToJson(this);

  @override
  String toString() {
    return toJson().toString();
  }
}

enum DecideGuardianHandleProposalRequestDecisionEnum {
  @JsonValue(r'approve')
  approve(r'approve'),
  @JsonValue(r'reject')
  reject(r'reject'),
  @JsonValue(r'unknown_default_open_api')
  unknownDefaultOpenApi(r'unknown_default_open_api');

  const DecideGuardianHandleProposalRequestDecisionEnum(this.value);

  final String value;

  @override
  String toString() => value;
}
