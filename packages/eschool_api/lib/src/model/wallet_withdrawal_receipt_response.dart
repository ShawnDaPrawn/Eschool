//
// AUTO-GENERATED FILE, DO NOT MODIFY!
//

// ignore_for_file: unused_element
import 'package:eschool_api/src/model/wallet_withdrawal_receipt.dart';
import 'package:json_annotation/json_annotation.dart';

part 'wallet_withdrawal_receipt_response.g.dart';

@JsonSerializable(
  checked: true,
  createToJson: true,
  disallowUnrecognizedKeys: false,
  explicitToJson: true,
)
class WalletWithdrawalReceiptResponse {
  /// Returns a new [WalletWithdrawalReceiptResponse] instance.
  WalletWithdrawalReceiptResponse({
    required this.error,

    required this.message,

    required this.data,

    required this.code,
  });

  @JsonKey(name: r'error', required: true, includeIfNull: false)
  final bool error;

  @JsonKey(name: r'message', required: true, includeIfNull: false)
  final String message;

  @JsonKey(name: r'data', required: true, includeIfNull: false)
  final WalletWithdrawalReceipt data;

  @JsonKey(name: r'code', required: true, includeIfNull: false)
  final int code;

  @override
  bool operator ==(Object other) =>
      identical(this, other) ||
      other is WalletWithdrawalReceiptResponse &&
          other.error == error &&
          other.message == message &&
          other.data == data &&
          other.code == code;

  @override
  int get hashCode =>
      error.hashCode + message.hashCode + data.hashCode + code.hashCode;

  factory WalletWithdrawalReceiptResponse.fromJson(Map<String, dynamic> json) =>
      _$WalletWithdrawalReceiptResponseFromJson(json);

  Map<String, dynamic> toJson() =>
      _$WalletWithdrawalReceiptResponseToJson(this);

  @override
  String toString() {
    return toJson().toString();
  }
}
