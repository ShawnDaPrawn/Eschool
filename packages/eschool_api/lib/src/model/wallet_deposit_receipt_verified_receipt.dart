//
// AUTO-GENERATED FILE, DO NOT MODIFY!
//

// ignore_for_file: unused_element
import 'package:json_annotation/json_annotation.dart';

part 'wallet_deposit_receipt_verified_receipt.g.dart';

@JsonSerializable(
  checked: true,
  createToJson: true,
  disallowUnrecognizedKeys: false,
  explicitToJson: true,
)
class WalletDepositReceiptVerifiedReceipt {
  /// Returns a new [WalletDepositReceiptVerifiedReceipt] instance.
  WalletDepositReceiptVerifiedReceipt({
    required this.reference,

    required this.currency,

    required this.amountCents,

    required this.method,

    required this.sourceReference,

    required this.receivedOn,

    required this.collectedBy,

    required this.feeCents,

    required this.netCents,

    required this.settlementReference,
  });

  @JsonKey(name: r'reference', required: true, includeIfNull: false)
  final String reference;

  @JsonKey(name: r'currency', required: true, includeIfNull: false)
  final String currency;

  // minimum: 1
  // maximum: 100000000000
  @JsonKey(name: r'amount_cents', required: true, includeIfNull: false)
  final int amountCents;

  @JsonKey(
    name: r'method',
    required: true,
    includeIfNull: false,
    unknownEnumValue:
        WalletDepositReceiptVerifiedReceiptMethodEnum.unknownDefaultOpenApi,
  )
  final WalletDepositReceiptVerifiedReceiptMethodEnum method;

  @JsonKey(name: r'source_reference', required: true, includeIfNull: false)
  final String sourceReference;

  @JsonKey(name: r'received_on', required: true, includeIfNull: false)
  final DateTime receivedOn;

  @JsonKey(
    name: r'collected_by',
    required: true,
    includeIfNull: false,
    unknownEnumValue: WalletDepositReceiptVerifiedReceiptCollectedByEnum
        .unknownDefaultOpenApi,
  )
  final WalletDepositReceiptVerifiedReceiptCollectedByEnum collectedBy;

  // minimum: 0
  // maximum: 0
  @JsonKey(name: r'fee_cents', required: true, includeIfNull: false)
  final int feeCents;

  // minimum: 1
  // maximum: 100000000000
  @JsonKey(name: r'net_cents', required: true, includeIfNull: false)
  final int netCents;

  @JsonKey(name: r'settlement_reference', required: true, includeIfNull: true)
  final String? settlementReference;

  @override
  bool operator ==(Object other) =>
      identical(this, other) ||
      other is WalletDepositReceiptVerifiedReceipt &&
          other.reference == reference &&
          other.currency == currency &&
          other.amountCents == amountCents &&
          other.method == method &&
          other.sourceReference == sourceReference &&
          other.receivedOn == receivedOn &&
          other.collectedBy == collectedBy &&
          other.feeCents == feeCents &&
          other.netCents == netCents &&
          other.settlementReference == settlementReference;

  @override
  int get hashCode =>
      reference.hashCode +
      currency.hashCode +
      amountCents.hashCode +
      method.hashCode +
      sourceReference.hashCode +
      receivedOn.hashCode +
      collectedBy.hashCode +
      feeCents.hashCode +
      netCents.hashCode +
      (settlementReference == null ? 0 : settlementReference.hashCode);

  factory WalletDepositReceiptVerifiedReceipt.fromJson(
    Map<String, dynamic> json,
  ) => _$WalletDepositReceiptVerifiedReceiptFromJson(json);

  Map<String, dynamic> toJson() =>
      _$WalletDepositReceiptVerifiedReceiptToJson(this);

  @override
  String toString() {
    return toJson().toString();
  }
}

enum WalletDepositReceiptVerifiedReceiptMethodEnum {
  @JsonValue(r'cash')
  cash(r'cash'),
  @JsonValue(r'eft')
  eft(r'eft'),
  @JsonValue(r'unknown_default_open_api')
  unknownDefaultOpenApi(r'unknown_default_open_api');

  const WalletDepositReceiptVerifiedReceiptMethodEnum(this.value);

  final String value;

  @override
  String toString() => value;
}

enum WalletDepositReceiptVerifiedReceiptCollectedByEnum {
  @JsonValue(r'school')
  school(r'school'),
  @JsonValue(r'unknown_default_open_api')
  unknownDefaultOpenApi(r'unknown_default_open_api');

  const WalletDepositReceiptVerifiedReceiptCollectedByEnum(this.value);

  final String value;

  @override
  String toString() => value;
}
