//
// AUTO-GENERATED FILE, DO NOT MODIFY!
//

// ignore_for_file: unused_element
import 'package:json_annotation/json_annotation.dart';

part 'wallet_withdrawal_write.g.dart';

@JsonSerializable(
  checked: true,
  createToJson: true,
  disallowUnrecognizedKeys: false,
  explicitToJson: true,
)
class WalletWithdrawalWrite {
  /// Returns a new [WalletWithdrawalWrite] instance.
  WalletWithdrawalWrite({
    required this.operationId,

    required this.amount,

    this.slotId,

    this.privateBankInstructions,

    required this.confirmed,
  });

  @JsonKey(name: r'operation_id', required: true, includeIfNull: false)
  final String operationId;

  @JsonKey(name: r'amount', required: true, includeIfNull: false)
  final String amount;

  // minimum: 1
  // maximum: 9000000000000000
  @JsonKey(name: r'slot_id', required: false, includeIfNull: false)
  final int? slotId;

  /// Required only for the bank method; original-holder instructions are encrypted and never returned in history.
  @JsonKey(
    name: r'private_bank_instructions',
    required: false,
    includeIfNull: false,
  )
  final String? privateBankInstructions;

  /// Must be true to reserve original available wallet funds for Finance approval.
  @JsonKey(name: r'confirmed', required: true, includeIfNull: false)
  final bool confirmed;

  @override
  bool operator ==(Object other) =>
      identical(this, other) ||
      other is WalletWithdrawalWrite &&
          other.operationId == operationId &&
          other.amount == amount &&
          other.slotId == slotId &&
          other.privateBankInstructions == privateBankInstructions &&
          other.confirmed == confirmed;

  @override
  int get hashCode =>
      operationId.hashCode +
      amount.hashCode +
      (slotId == null ? 0 : slotId.hashCode) +
      (privateBankInstructions == null ? 0 : privateBankInstructions.hashCode) +
      confirmed.hashCode;

  factory WalletWithdrawalWrite.fromJson(Map<String, dynamic> json) =>
      _$WalletWithdrawalWriteFromJson(json);

  Map<String, dynamic> toJson() => _$WalletWithdrawalWriteToJson(this);

  @override
  String toString() {
    return toJson().toString();
  }
}
