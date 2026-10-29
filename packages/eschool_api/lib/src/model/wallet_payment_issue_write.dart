//
// AUTO-GENERATED FILE, DO NOT MODIFY!
//

// ignore_for_file: unused_element
import 'package:json_annotation/json_annotation.dart';

part 'wallet_payment_issue_write.g.dart';

@JsonSerializable(
  checked: true,
  createToJson: true,
  disallowUnrecognizedKeys: false,
  explicitToJson: true,
)
class WalletPaymentIssueWrite {
  /// Returns a new [WalletPaymentIssueWrite] instance.
  WalletPaymentIssueWrite({
    required this.operationId,

    required this.quote,

    required this.confirmed,
  });

  @JsonKey(name: r'operation_id', required: true, includeIfNull: false)
  final String operationId;

  @JsonKey(name: r'quote', required: true, includeIfNull: false)
  final String quote;

  /// Must be true after reviewing the quoted original wallet, products, quantities, tax and total.
  @JsonKey(name: r'confirmed', required: true, includeIfNull: false)
  final bool confirmed;

  @override
  bool operator ==(Object other) =>
      identical(this, other) ||
      other is WalletPaymentIssueWrite &&
          other.operationId == operationId &&
          other.quote == quote &&
          other.confirmed == confirmed;

  @override
  int get hashCode =>
      operationId.hashCode + quote.hashCode + confirmed.hashCode;

  factory WalletPaymentIssueWrite.fromJson(Map<String, dynamic> json) =>
      _$WalletPaymentIssueWriteFromJson(json);

  Map<String, dynamic> toJson() => _$WalletPaymentIssueWriteToJson(this);

  @override
  String toString() {
    return toJson().toString();
  }
}
