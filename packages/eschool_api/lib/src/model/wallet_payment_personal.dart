//
// AUTO-GENERATED FILE, DO NOT MODIFY!
//

// ignore_for_file: unused_element
import 'package:eschool_api/src/model/commerce_seller.dart';
import 'package:eschool_api/src/model/commerce_quote_line.dart';
import 'package:json_annotation/json_annotation.dart';

part 'wallet_payment_personal.g.dart';

@JsonSerializable(
  checked: true,
  createToJson: true,
  disallowUnrecognizedKeys: false,
  explicitToJson: true,
)
class WalletPaymentPersonal {
  /// Returns a new [WalletPaymentPersonal] instance.
  WalletPaymentPersonal({
    required this.schoolId,

    required this.actorUserId,

    required this.id,

    required this.vendorId,

    required this.kind,

    required this.currency,

    required this.amountCents,

    required this.netCents,

    required this.vatCents,

    required this.expiresAt,

    required this.seller,

    required this.lines,

    required this.status,

    required this.qr,

    required this.orderId,

    required this.walletId,

    required this.walletOwnerId,

    required this.walletOwnerName,
  });

  // minimum: 1
  // maximum: 9000000000000000
  @JsonKey(name: r'school_id', required: true, includeIfNull: false)
  final int schoolId;

  // minimum: 1
  // maximum: 9000000000000000
  @JsonKey(name: r'actor_user_id', required: true, includeIfNull: false)
  final int actorUserId;

  @JsonKey(name: r'id', required: true, includeIfNull: false)
  final String id;

  // minimum: 1
  // maximum: 9000000000000000
  @JsonKey(name: r'vendor_id', required: true, includeIfNull: false)
  final int vendorId;

  @JsonKey(
    name: r'kind',
    required: true,
    includeIfNull: false,
    unknownEnumValue: WalletPaymentPersonalKindEnum.unknownDefaultOpenApi,
  )
  final WalletPaymentPersonalKindEnum kind;

  @JsonKey(name: r'currency', required: true, includeIfNull: false)
  final String currency;

  // minimum: 0
  // maximum: 100000000000
  @JsonKey(name: r'amount_cents', required: true, includeIfNull: false)
  final int amountCents;

  // minimum: 0
  // maximum: 100000000000
  @JsonKey(name: r'net_cents', required: true, includeIfNull: false)
  final int netCents;

  // minimum: 0
  // maximum: 100000000000
  @JsonKey(name: r'vat_cents', required: true, includeIfNull: false)
  final int vatCents;

  @JsonKey(name: r'expires_at', required: true, includeIfNull: false)
  final DateTime expiresAt;

  @JsonKey(name: r'seller', required: true, includeIfNull: false)
  final CommerceSeller seller;

  @JsonKey(name: r'lines', required: true, includeIfNull: false)
  final List<CommerceQuoteLine> lines;

  @JsonKey(
    name: r'status',
    required: true,
    includeIfNull: false,
    unknownEnumValue: WalletPaymentPersonalStatusEnum.unknownDefaultOpenApi,
  )
  final WalletPaymentPersonalStatusEnum status;

  @JsonKey(name: r'qr', required: true, includeIfNull: true)
  final String? qr;

  // minimum: 1
  // maximum: 9000000000000000
  @JsonKey(name: r'order_id', required: true, includeIfNull: true)
  final int? orderId;

  // minimum: 1
  // maximum: 9000000000000000
  @JsonKey(name: r'wallet_id', required: true, includeIfNull: false)
  final int walletId;

  // minimum: 1
  // maximum: 9000000000000000
  @JsonKey(name: r'wallet_owner_id', required: true, includeIfNull: false)
  final int walletOwnerId;

  @JsonKey(name: r'wallet_owner_name', required: true, includeIfNull: false)
  final String walletOwnerName;

  @override
  bool operator ==(Object other) =>
      identical(this, other) ||
      other is WalletPaymentPersonal &&
          other.schoolId == schoolId &&
          other.actorUserId == actorUserId &&
          other.id == id &&
          other.vendorId == vendorId &&
          other.kind == kind &&
          other.currency == currency &&
          other.amountCents == amountCents &&
          other.netCents == netCents &&
          other.vatCents == vatCents &&
          other.expiresAt == expiresAt &&
          other.seller == seller &&
          other.lines == lines &&
          other.status == status &&
          other.qr == qr &&
          other.orderId == orderId &&
          other.walletId == walletId &&
          other.walletOwnerId == walletOwnerId &&
          other.walletOwnerName == walletOwnerName;

  @override
  int get hashCode =>
      schoolId.hashCode +
      actorUserId.hashCode +
      id.hashCode +
      vendorId.hashCode +
      kind.hashCode +
      currency.hashCode +
      amountCents.hashCode +
      netCents.hashCode +
      vatCents.hashCode +
      expiresAt.hashCode +
      seller.hashCode +
      lines.hashCode +
      status.hashCode +
      (qr == null ? 0 : qr.hashCode) +
      (orderId == null ? 0 : orderId.hashCode) +
      walletId.hashCode +
      walletOwnerId.hashCode +
      walletOwnerName.hashCode;

  factory WalletPaymentPersonal.fromJson(Map<String, dynamic> json) =>
      _$WalletPaymentPersonalFromJson(json);

  Map<String, dynamic> toJson() => _$WalletPaymentPersonalToJson(this);

  @override
  String toString() {
    return toJson().toString();
  }
}

enum WalletPaymentPersonalKindEnum {
  @JsonValue(r'qr')
  qr(r'qr'),
  @JsonValue(r'sun')
  sun(r'sun'),
  @JsonValue(r'unknown_default_open_api')
  unknownDefaultOpenApi(r'unknown_default_open_api');

  const WalletPaymentPersonalKindEnum(this.value);

  final String value;

  @override
  String toString() => value;
}

enum WalletPaymentPersonalStatusEnum {
  @JsonValue(r'awaiting_counter')
  awaitingCounter(r'awaiting_counter'),
  @JsonValue(r'paid')
  paid(r'paid'),
  @JsonValue(r'expired')
  expired(r'expired'),
  @JsonValue(r'cancelled')
  cancelled(r'cancelled'),
  @JsonValue(r'unknown_default_open_api')
  unknownDefaultOpenApi(r'unknown_default_open_api');

  const WalletPaymentPersonalStatusEnum(this.value);

  final String value;

  @override
  String toString() => value;
}
