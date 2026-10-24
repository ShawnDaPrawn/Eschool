//
// AUTO-GENERATED FILE, DO NOT MODIFY!
//

// ignore_for_file: unused_element
import 'package:json_annotation/json_annotation.dart';

part 'communication_withdraw_write.g.dart';

@JsonSerializable(
  checked: true,
  createToJson: true,
  disallowUnrecognizedKeys: false,
  explicitToJson: true,
)
class CommunicationWithdrawWrite {
  /// Returns a new [CommunicationWithdrawWrite] instance.
  CommunicationWithdrawWrite({
    required this.revision,

    required this.operationId,

    required this.reason,
  });

  // minimum: 1
  @JsonKey(name: r'revision', required: true, includeIfNull: false)
  final int revision;

  @JsonKey(name: r'operation_id', required: true, includeIfNull: false)
  final String operationId;

  @JsonKey(name: r'reason', required: true, includeIfNull: false)
  final String reason;

  @override
  bool operator ==(Object other) =>
      identical(this, other) ||
      other is CommunicationWithdrawWrite &&
          other.revision == revision &&
          other.operationId == operationId &&
          other.reason == reason;

  @override
  int get hashCode =>
      revision.hashCode + operationId.hashCode + reason.hashCode;

  factory CommunicationWithdrawWrite.fromJson(Map<String, dynamic> json) =>
      _$CommunicationWithdrawWriteFromJson(json);

  Map<String, dynamic> toJson() => _$CommunicationWithdrawWriteToJson(this);

  @override
  String toString() {
    return toJson().toString();
  }
}
