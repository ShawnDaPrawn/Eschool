//
// AUTO-GENERATED FILE, DO NOT MODIFY!
//

// ignore_for_file: unused_element
import 'package:json_annotation/json_annotation.dart';

part 'wallet_deposit_claim_write.g.dart';

@JsonSerializable(
  checked: true,
  createToJson: true,
  disallowUnrecognizedKeys: false,
  explicitToJson: true,
)
class WalletDepositClaimWrite {
  /// Returns a new [WalletDepositClaimWrite] instance.
  WalletDepositClaimWrite({
    required this.operationId,

    required this.amount,

    required this.method,

    required this.sourceReference,

    required this.paidOn,

    required this.evidence,

    required this.confirmed,
  });

  @JsonKey(name: r'operation_id', required: true, includeIfNull: false)
  final String operationId;

  @JsonKey(name: r'amount', required: true, includeIfNull: false)
  final String amount;

  @JsonKey(
    name: r'method',
    required: true,
    includeIfNull: false,
    unknownEnumValue: WalletDepositClaimWriteMethodEnum.unknownDefaultOpenApi,
  )
  final WalletDepositClaimWriteMethodEnum method;

  @JsonKey(name: r'source_reference', required: true, includeIfNull: false)
  final String sourceReference;

  @JsonKey(name: r'paid_on', required: true, includeIfNull: false)
  final DateTime paidOn;

  @JsonKey(name: r'evidence', required: true, includeIfNull: false)
  final String evidence;

  /// Must be true to acknowledge that Finance must independently verify the claimed deposit.
  @JsonKey(name: r'confirmed', required: true, includeIfNull: false)
  final bool confirmed;

  @override
  bool operator ==(Object other) =>
      identical(this, other) ||
      other is WalletDepositClaimWrite &&
          other.operationId == operationId &&
          other.amount == amount &&
          other.method == method &&
          other.sourceReference == sourceReference &&
          other.paidOn == paidOn &&
          other.evidence == evidence &&
          other.confirmed == confirmed;

  @override
  int get hashCode =>
      operationId.hashCode +
      amount.hashCode +
      method.hashCode +
      sourceReference.hashCode +
      paidOn.hashCode +
      evidence.hashCode +
      confirmed.hashCode;

  factory WalletDepositClaimWrite.fromJson(Map<String, dynamic> json) =>
      _$WalletDepositClaimWriteFromJson(json);

  Map<String, dynamic> toJson() => _$WalletDepositClaimWriteToJson(this);

  @override
  String toString() {
    return toJson().toString();
  }
}

enum WalletDepositClaimWriteMethodEnum {
  @JsonValue(r'cash')
  cash(r'cash'),
  @JsonValue(r'eft')
  eft(r'eft'),
  @JsonValue(r'unknown_default_open_api')
  unknownDefaultOpenApi(r'unknown_default_open_api');

  const WalletDepositClaimWriteMethodEnum(this.value);

  final String value;

  @override
  String toString() => value;
}
