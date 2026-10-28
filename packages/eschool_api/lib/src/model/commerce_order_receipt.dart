//
// AUTO-GENERATED FILE, DO NOT MODIFY!
//

// ignore_for_file: unused_element
import 'package:eschool_api/src/model/commerce_receipt_line.dart';
import 'package:eschool_api/src/model/commerce_seller.dart';
import 'package:json_annotation/json_annotation.dart';

part 'commerce_order_receipt.g.dart';

@JsonSerializable(
  checked: true,
  createToJson: true,
  disallowUnrecognizedKeys: false,
  explicitToJson: true,
)
class CommerceOrderReceipt {
  /// Returns a new [CommerceOrderReceipt] instance.
  CommerceOrderReceipt({
    required this.id,

    required this.schoolId,

    required this.actorUserId,

    required this.operationId,

    required this.orderNumber,

    required this.walletId,

    required this.walletOwnerId,

    required this.learnerId,

    required this.vendorId,

    required this.currency,

    required this.amountCents,

    required this.netCents,

    required this.vatCents,

    required this.status,

    required this.canCancel,

    required this.qr,

    required this.createdAt,

    required this.processedAt,

    required this.seller,

    required this.lines,
  });

  // minimum: 1
  // maximum: 9000000000000000
  @JsonKey(name: r'id', required: true, includeIfNull: false)
  final int id;

  // minimum: 1
  // maximum: 9000000000000000
  @JsonKey(name: r'school_id', required: true, includeIfNull: false)
  final int schoolId;

  // minimum: 1
  // maximum: 9000000000000000
  @JsonKey(name: r'actor_user_id', required: true, includeIfNull: false)
  final int actorUserId;

  @JsonKey(name: r'operation_id', required: true, includeIfNull: false)
  final String operationId;

  @JsonKey(name: r'order_number', required: true, includeIfNull: false)
  final String orderNumber;

  // minimum: 1
  // maximum: 9000000000000000
  @JsonKey(name: r'wallet_id', required: true, includeIfNull: false)
  final int walletId;

  // minimum: 1
  // maximum: 9000000000000000
  @JsonKey(name: r'wallet_owner_id', required: true, includeIfNull: false)
  final int walletOwnerId;

  // minimum: 1
  // maximum: 9000000000000000
  @JsonKey(name: r'learner_id', required: true, includeIfNull: true)
  final int? learnerId;

  // minimum: 1
  // maximum: 9000000000000000
  @JsonKey(name: r'vendor_id', required: true, includeIfNull: false)
  final int vendorId;

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

  @JsonKey(
    name: r'status',
    required: true,
    includeIfNull: false,
    unknownEnumValue: CommerceOrderReceiptStatusEnum.unknownDefaultOpenApi,
  )
  final CommerceOrderReceiptStatusEnum status;

  @JsonKey(name: r'can_cancel', required: true, includeIfNull: false)
  final bool canCancel;

  @JsonKey(name: r'qr', required: true, includeIfNull: true)
  final String? qr;

  @JsonKey(name: r'created_at', required: true, includeIfNull: false)
  final DateTime createdAt;

  @JsonKey(name: r'processed_at', required: true, includeIfNull: true)
  final DateTime? processedAt;

  @JsonKey(name: r'seller', required: true, includeIfNull: false)
  final CommerceSeller seller;

  @JsonKey(name: r'lines', required: true, includeIfNull: false)
  final List<CommerceReceiptLine> lines;

  @override
  bool operator ==(Object other) =>
      identical(this, other) ||
      other is CommerceOrderReceipt &&
          other.id == id &&
          other.schoolId == schoolId &&
          other.actorUserId == actorUserId &&
          other.operationId == operationId &&
          other.orderNumber == orderNumber &&
          other.walletId == walletId &&
          other.walletOwnerId == walletOwnerId &&
          other.learnerId == learnerId &&
          other.vendorId == vendorId &&
          other.currency == currency &&
          other.amountCents == amountCents &&
          other.netCents == netCents &&
          other.vatCents == vatCents &&
          other.status == status &&
          other.canCancel == canCancel &&
          other.qr == qr &&
          other.createdAt == createdAt &&
          other.processedAt == processedAt &&
          other.seller == seller &&
          other.lines == lines;

  @override
  int get hashCode =>
      id.hashCode +
      schoolId.hashCode +
      actorUserId.hashCode +
      operationId.hashCode +
      orderNumber.hashCode +
      walletId.hashCode +
      walletOwnerId.hashCode +
      (learnerId == null ? 0 : learnerId.hashCode) +
      vendorId.hashCode +
      currency.hashCode +
      amountCents.hashCode +
      netCents.hashCode +
      vatCents.hashCode +
      status.hashCode +
      canCancel.hashCode +
      (qr == null ? 0 : qr.hashCode) +
      createdAt.hashCode +
      (processedAt == null ? 0 : processedAt.hashCode) +
      seller.hashCode +
      lines.hashCode;

  factory CommerceOrderReceipt.fromJson(Map<String, dynamic> json) =>
      _$CommerceOrderReceiptFromJson(json);

  Map<String, dynamic> toJson() => _$CommerceOrderReceiptToJson(this);

  @override
  String toString() {
    return toJson().toString();
  }
}

enum CommerceOrderReceiptStatusEnum {
  @JsonValue(r'ready')
  ready(r'ready'),
  @JsonValue(r'collected')
  collected(r'collected'),
  @JsonValue(r'cancelled')
  cancelled(r'cancelled'),
  @JsonValue(r'unknown_default_open_api')
  unknownDefaultOpenApi(r'unknown_default_open_api');

  const CommerceOrderReceiptStatusEnum(this.value);

  final String value;

  @override
  String toString() => value;
}
