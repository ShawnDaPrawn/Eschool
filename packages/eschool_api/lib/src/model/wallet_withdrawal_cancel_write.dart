//
// AUTO-GENERATED FILE, DO NOT MODIFY!
//

// ignore_for_file: unused_element
import 'package:json_annotation/json_annotation.dart';

part 'wallet_withdrawal_cancel_write.g.dart';

@JsonSerializable(
  checked: true,
  createToJson: true,
  disallowUnrecognizedKeys: false,
  explicitToJson: true,
)
class WalletWithdrawalCancelWrite {
  /// Returns a new [WalletWithdrawalCancelWrite] instance.
  WalletWithdrawalCancelWrite({
    required this.operationId,

    required this.privateReason,

    required this.confirmed,
  });

  @JsonKey(name: r'operation_id', required: true, includeIfNull: false)
  final String operationId;

  @JsonKey(name: r'private_reason', required: true, includeIfNull: false)
  final String privateReason;

  /// Must be true to release the reservation only if no outside payout occurred.
  @JsonKey(name: r'confirmed', required: true, includeIfNull: false)
  final bool confirmed;

  @override
  bool operator ==(Object other) =>
      identical(this, other) ||
      other is WalletWithdrawalCancelWrite &&
          other.operationId == operationId &&
          other.privateReason == privateReason &&
          other.confirmed == confirmed;

  @override
  int get hashCode =>
      operationId.hashCode + privateReason.hashCode + confirmed.hashCode;

  factory WalletWithdrawalCancelWrite.fromJson(Map<String, dynamic> json) =>
      _$WalletWithdrawalCancelWriteFromJson(json);

  Map<String, dynamic> toJson() => _$WalletWithdrawalCancelWriteToJson(this);

  @override
  String toString() {
    return toJson().toString();
  }
}
