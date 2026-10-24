//
// AUTO-GENERATED FILE, DO NOT MODIFY!
//

// ignore_for_file: unused_element
import 'package:json_annotation/json_annotation.dart';

part 'communication_decision_write.g.dart';

@JsonSerializable(
  checked: true,
  createToJson: true,
  disallowUnrecognizedKeys: false,
  explicitToJson: true,
)
class CommunicationDecisionWrite {
  /// Returns a new [CommunicationDecisionWrite] instance.
  CommunicationDecisionWrite({
    required this.revision,

    required this.operationId,

    required this.fingerprint,

    required this.decision,

    required this.reason,
  });

  // minimum: 1
  @JsonKey(name: r'revision', required: true, includeIfNull: false)
  final int revision;

  @JsonKey(name: r'operation_id', required: true, includeIfNull: false)
  final String operationId;

  @JsonKey(name: r'fingerprint', required: true, includeIfNull: false)
  final String fingerprint;

  @JsonKey(
    name: r'decision',
    required: true,
    includeIfNull: false,
    unknownEnumValue:
        CommunicationDecisionWriteDecisionEnum.unknownDefaultOpenApi,
  )
  final CommunicationDecisionWriteDecisionEnum decision;

  @JsonKey(name: r'reason', required: true, includeIfNull: false)
  final String reason;

  @override
  bool operator ==(Object other) =>
      identical(this, other) ||
      other is CommunicationDecisionWrite &&
          other.revision == revision &&
          other.operationId == operationId &&
          other.fingerprint == fingerprint &&
          other.decision == decision &&
          other.reason == reason;

  @override
  int get hashCode =>
      revision.hashCode +
      operationId.hashCode +
      fingerprint.hashCode +
      decision.hashCode +
      reason.hashCode;

  factory CommunicationDecisionWrite.fromJson(Map<String, dynamic> json) =>
      _$CommunicationDecisionWriteFromJson(json);

  Map<String, dynamic> toJson() => _$CommunicationDecisionWriteToJson(this);

  @override
  String toString() {
    return toJson().toString();
  }
}

enum CommunicationDecisionWriteDecisionEnum {
  @JsonValue(r'approve')
  approve(r'approve'),
  @JsonValue(r'reject')
  reject(r'reject'),
  @JsonValue(r'unknown_default_open_api')
  unknownDefaultOpenApi(r'unknown_default_open_api');

  const CommunicationDecisionWriteDecisionEnum(this.value);

  final String value;

  @override
  String toString() => value;
}
