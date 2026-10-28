//
// AUTO-GENERATED FILE, DO NOT MODIFY!
//

// ignore_for_file: unused_element
import 'package:json_annotation/json_annotation.dart';

part 'wallet_desk_receipt.g.dart';

@JsonSerializable(
  checked: true,
  createToJson: true,
  disallowUnrecognizedKeys: false,
  explicitToJson: true,
)
class WalletDeskReceipt {
  /// Returns a new [WalletDeskReceipt] instance.
  WalletDeskReceipt({
    required this.id,

    required this.reference,

    required this.currency,

    required this.amountCents,

    required this.status,

    required this.createdAt,

    required this.processedAt,

    required this.collectedBy,

    required this.feeCents,

    required this.netCents,
  });

  // minimum: 1
  // maximum: 9000000000000000
  @JsonKey(name: r'id', required: true, includeIfNull: false)
  final int id;

  @JsonKey(name: r'reference', required: true, includeIfNull: false)
  final String reference;

  @JsonKey(name: r'currency', required: true, includeIfNull: false)
  final String currency;

  // minimum: 1
  // maximum: 100000000000
  @JsonKey(name: r'amount_cents', required: true, includeIfNull: false)
  final int amountCents;

  @JsonKey(
    name: r'status',
    required: true,
    includeIfNull: false,
    unknownEnumValue: WalletDeskReceiptStatusEnum.unknownDefaultOpenApi,
  )
  final WalletDeskReceiptStatusEnum status;

  @JsonKey(name: r'created_at', required: true, includeIfNull: false)
  final DateTime createdAt;

  @JsonKey(name: r'processed_at', required: true, includeIfNull: true)
  final DateTime? processedAt;

  @JsonKey(
    name: r'collected_by',
    required: true,
    includeIfNull: false,
    unknownEnumValue: WalletDeskReceiptCollectedByEnum.unknownDefaultOpenApi,
  )
  final WalletDeskReceiptCollectedByEnum collectedBy;

  @JsonKey(
    name: r'fee_cents',
    required: true,
    includeIfNull: false,
    unknownEnumValue: WalletDeskReceiptFeeCentsEnum.unknownDefaultOpenApi,
  )
  final WalletDeskReceiptFeeCentsEnum feeCents;

  // minimum: 1
  // maximum: 100000000000
  @JsonKey(name: r'net_cents', required: true, includeIfNull: false)
  final int netCents;

  @override
  bool operator ==(Object other) =>
      identical(this, other) ||
      other is WalletDeskReceipt &&
          other.id == id &&
          other.reference == reference &&
          other.currency == currency &&
          other.amountCents == amountCents &&
          other.status == status &&
          other.createdAt == createdAt &&
          other.processedAt == processedAt &&
          other.collectedBy == collectedBy &&
          other.feeCents == feeCents &&
          other.netCents == netCents;

  @override
  int get hashCode =>
      id.hashCode +
      reference.hashCode +
      currency.hashCode +
      amountCents.hashCode +
      status.hashCode +
      createdAt.hashCode +
      (processedAt == null ? 0 : processedAt.hashCode) +
      collectedBy.hashCode +
      feeCents.hashCode +
      netCents.hashCode;

  factory WalletDeskReceipt.fromJson(Map<String, dynamic> json) =>
      _$WalletDeskReceiptFromJson(json);

  Map<String, dynamic> toJson() => _$WalletDeskReceiptToJson(this);

  @override
  String toString() {
    return toJson().toString();
  }
}

enum WalletDeskReceiptStatusEnum {
  @JsonValue(r'pending')
  pending(r'pending'),
  @JsonValue(r'released')
  released(r'released'),
  @JsonValue(r'cancelled')
  cancelled(r'cancelled'),
  @JsonValue(r'unknown_default_open_api')
  unknownDefaultOpenApi(r'unknown_default_open_api');

  const WalletDeskReceiptStatusEnum(this.value);

  final String value;

  @override
  String toString() => value;
}

enum WalletDeskReceiptCollectedByEnum {
  @JsonValue(r'school')
  school(r'school'),
  @JsonValue(r'unknown_default_open_api')
  unknownDefaultOpenApi(r'unknown_default_open_api');

  const WalletDeskReceiptCollectedByEnum(this.value);

  final String value;

  @override
  String toString() => value;
}

enum WalletDeskReceiptFeeCentsEnum {
  @JsonValue(0)
  number0('0'),
  @JsonValue(11184809)
  unknownDefaultOpenApi('11184809');

  const WalletDeskReceiptFeeCentsEnum(this.value);

  final String value;

  @override
  String toString() => value;
}
