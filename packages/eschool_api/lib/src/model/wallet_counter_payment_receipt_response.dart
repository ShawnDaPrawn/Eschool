//
// AUTO-GENERATED FILE, DO NOT MODIFY!
//

// ignore_for_file: unused_element
import 'package:eschool_api/src/model/wallet_counter_payment_receipt.dart';
import 'package:json_annotation/json_annotation.dart';

part 'wallet_counter_payment_receipt_response.g.dart';

@JsonSerializable(
  checked: true,
  createToJson: true,
  disallowUnrecognizedKeys: false,
  explicitToJson: true,
)
class WalletCounterPaymentReceiptResponse {
  /// Returns a new [WalletCounterPaymentReceiptResponse] instance.
  WalletCounterPaymentReceiptResponse({
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
  final WalletCounterPaymentReceipt data;

  @JsonKey(name: r'code', required: true, includeIfNull: false)
  final int code;

  @override
  bool operator ==(Object other) =>
      identical(this, other) ||
      other is WalletCounterPaymentReceiptResponse &&
          other.error == error &&
          other.message == message &&
          other.data == data &&
          other.code == code;

  @override
  int get hashCode =>
      error.hashCode + message.hashCode + data.hashCode + code.hashCode;

  factory WalletCounterPaymentReceiptResponse.fromJson(
    Map<String, dynamic> json,
  ) => _$WalletCounterPaymentReceiptResponseFromJson(json);

  Map<String, dynamic> toJson() =>
      _$WalletCounterPaymentReceiptResponseToJson(this);

  @override
  String toString() {
    return toJson().toString();
  }
}
