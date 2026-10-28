//
// AUTO-GENERATED FILE, DO NOT MODIFY!
//

// ignore_for_file: unused_element
import 'package:json_annotation/json_annotation.dart';

part 'wallet_deposit_cancel_write.g.dart';

@JsonSerializable(
  checked: true,
  createToJson: true,
  disallowUnrecognizedKeys: false,
  explicitToJson: true,
)
class WalletDepositCancelWrite {
  /// Returns a new [WalletDepositCancelWrite] instance.
  WalletDepositCancelWrite({
    required this.operationId,

    required this.confirmed,
  });

  @JsonKey(name: r'operation_id', required: true, includeIfNull: false)
  final String operationId;

  /// Must be true to withdraw this pending verification request without moving money.
  @JsonKey(name: r'confirmed', required: true, includeIfNull: false)
  final bool confirmed;

  @override
  bool operator ==(Object other) =>
      identical(this, other) ||
      other is WalletDepositCancelWrite &&
          other.operationId == operationId &&
          other.confirmed == confirmed;

  @override
  int get hashCode => operationId.hashCode + confirmed.hashCode;

  factory WalletDepositCancelWrite.fromJson(Map<String, dynamic> json) =>
      _$WalletDepositCancelWriteFromJson(json);

  Map<String, dynamic> toJson() => _$WalletDepositCancelWriteToJson(this);

  @override
  String toString() {
    return toJson().toString();
  }
}
