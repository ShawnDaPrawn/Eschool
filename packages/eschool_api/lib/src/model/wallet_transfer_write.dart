//
// AUTO-GENERATED FILE, DO NOT MODIFY!
//

// ignore_for_file: unused_element
import 'package:json_annotation/json_annotation.dart';

part 'wallet_transfer_write.g.dart';

@JsonSerializable(
  checked: true,
  createToJson: true,
  disallowUnrecognizedKeys: false,
  explicitToJson: true,
)
class WalletTransferWrite {
  /// Returns a new [WalletTransferWrite] instance.
  WalletTransferWrite({
    required this.operationId,

    required this.fromWalletId,

    required this.toWalletId,

    required this.amount,

    required this.confirmed,
  });

  @JsonKey(name: r'operation_id', required: true, includeIfNull: false)
  final String operationId;

  // minimum: 1
  // maximum: 9000000000000000
  @JsonKey(name: r'from_wallet_id', required: true, includeIfNull: false)
  final int fromWalletId;

  // minimum: 1
  // maximum: 9000000000000000
  @JsonKey(name: r'to_wallet_id', required: true, includeIfNull: false)
  final int toWalletId;

  @JsonKey(name: r'amount', required: true, includeIfNull: false)
  final String amount;

  /// Must be true to confirm the reviewed source, recipient and amount.
  @JsonKey(name: r'confirmed', required: true, includeIfNull: false)
  final bool confirmed;

  @override
  bool operator ==(Object other) =>
      identical(this, other) ||
      other is WalletTransferWrite &&
          other.operationId == operationId &&
          other.fromWalletId == fromWalletId &&
          other.toWalletId == toWalletId &&
          other.amount == amount &&
          other.confirmed == confirmed;

  @override
  int get hashCode =>
      operationId.hashCode +
      fromWalletId.hashCode +
      toWalletId.hashCode +
      amount.hashCode +
      confirmed.hashCode;

  factory WalletTransferWrite.fromJson(Map<String, dynamic> json) =>
      _$WalletTransferWriteFromJson(json);

  Map<String, dynamic> toJson() => _$WalletTransferWriteToJson(this);

  @override
  String toString() {
    return toJson().toString();
  }
}
