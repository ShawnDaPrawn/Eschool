//
// AUTO-GENERATED FILE, DO NOT MODIFY!
//

// ignore_for_file: unused_element
import 'package:eschool_api/src/model/vendor_settlement_line.dart';
import 'package:json_annotation/json_annotation.dart';

part 'vendor_settlement_receipt.g.dart';

@JsonSerializable(
  checked: true,
  createToJson: true,
  disallowUnrecognizedKeys: false,
  explicitToJson: true,
)
class VendorSettlementReceipt {
  /// Returns a new [VendorSettlementReceipt] instance.
  VendorSettlementReceipt({
    required this.schoolId,

    required this.actorUserId,

    required this.vendorId,

    required this.id,

    required this.method,

    required this.currency,

    required this.periodFrom,

    required this.periodTo,

    required this.orderCount,

    required this.grossCents,

    required this.salesNetCents,

    required this.vatCents,

    required this.collectedBy,

    required this.collectorFeeCents,

    required this.payoutCents,

    required this.status,

    required this.createdAt,

    required this.settledAt,

    required this.paidOn,

    required this.lines,
  });

  // minimum: 1
  // maximum: 100000000000
  @JsonKey(name: r'school_id', required: true, includeIfNull: false)
  final int schoolId;

  // minimum: 1
  // maximum: 100000000000
  @JsonKey(name: r'actor_user_id', required: true, includeIfNull: false)
  final int actorUserId;

  // minimum: 1
  // maximum: 100000000000
  @JsonKey(name: r'vendor_id', required: true, includeIfNull: false)
  final int vendorId;

  // minimum: 1
  // maximum: 100000000000
  @JsonKey(name: r'id', required: true, includeIfNull: false)
  final int id;

  @JsonKey(
    name: r'method',
    required: true,
    includeIfNull: false,
    unknownEnumValue: VendorSettlementReceiptMethodEnum.unknownDefaultOpenApi,
  )
  final VendorSettlementReceiptMethodEnum method;

  @JsonKey(name: r'currency', required: true, includeIfNull: false)
  final String currency;

  @JsonKey(name: r'period_from', required: true, includeIfNull: false)
  final DateTime periodFrom;

  @JsonKey(name: r'period_to', required: true, includeIfNull: false)
  final DateTime periodTo;

  // minimum: 1
  // maximum: 500
  @JsonKey(name: r'order_count', required: true, includeIfNull: false)
  final int orderCount;

  // minimum: 0
  // maximum: 100000000000
  @JsonKey(name: r'gross_cents', required: true, includeIfNull: false)
  final int grossCents;

  // minimum: 0
  // maximum: 100000000000
  @JsonKey(name: r'sales_net_cents', required: true, includeIfNull: false)
  final int salesNetCents;

  // minimum: 0
  // maximum: 100000000000
  @JsonKey(name: r'vat_cents', required: true, includeIfNull: false)
  final int vatCents;

  @JsonKey(
    name: r'collected_by',
    required: true,
    includeIfNull: false,
    unknownEnumValue:
        VendorSettlementReceiptCollectedByEnum.unknownDefaultOpenApi,
  )
  final VendorSettlementReceiptCollectedByEnum collectedBy;

  // minimum: 0
  // maximum: 0
  @JsonKey(name: r'collector_fee_cents', required: true, includeIfNull: false)
  final int collectorFeeCents;

  // minimum: 0
  // maximum: 100000000000
  @JsonKey(name: r'payout_cents', required: true, includeIfNull: false)
  final int payoutCents;

  @JsonKey(
    name: r'status',
    required: true,
    includeIfNull: false,
    unknownEnumValue: VendorSettlementReceiptStatusEnum.unknownDefaultOpenApi,
  )
  final VendorSettlementReceiptStatusEnum status;

  @JsonKey(name: r'created_at', required: true, includeIfNull: false)
  final DateTime createdAt;

  @JsonKey(name: r'settled_at', required: true, includeIfNull: true)
  final DateTime? settledAt;

  @JsonKey(name: r'paid_on', required: true, includeIfNull: true)
  final DateTime? paidOn;

  @JsonKey(name: r'lines', required: true, includeIfNull: false)
  final List<VendorSettlementLine> lines;

  @override
  bool operator ==(Object other) =>
      identical(this, other) ||
      other is VendorSettlementReceipt &&
          other.schoolId == schoolId &&
          other.actorUserId == actorUserId &&
          other.vendorId == vendorId &&
          other.id == id &&
          other.method == method &&
          other.currency == currency &&
          other.periodFrom == periodFrom &&
          other.periodTo == periodTo &&
          other.orderCount == orderCount &&
          other.grossCents == grossCents &&
          other.salesNetCents == salesNetCents &&
          other.vatCents == vatCents &&
          other.collectedBy == collectedBy &&
          other.collectorFeeCents == collectorFeeCents &&
          other.payoutCents == payoutCents &&
          other.status == status &&
          other.createdAt == createdAt &&
          other.settledAt == settledAt &&
          other.paidOn == paidOn &&
          other.lines == lines;

  @override
  int get hashCode =>
      schoolId.hashCode +
      actorUserId.hashCode +
      vendorId.hashCode +
      id.hashCode +
      method.hashCode +
      currency.hashCode +
      periodFrom.hashCode +
      periodTo.hashCode +
      orderCount.hashCode +
      grossCents.hashCode +
      salesNetCents.hashCode +
      vatCents.hashCode +
      collectedBy.hashCode +
      collectorFeeCents.hashCode +
      payoutCents.hashCode +
      status.hashCode +
      createdAt.hashCode +
      (settledAt == null ? 0 : settledAt.hashCode) +
      (paidOn == null ? 0 : paidOn.hashCode) +
      lines.hashCode;

  factory VendorSettlementReceipt.fromJson(Map<String, dynamic> json) =>
      _$VendorSettlementReceiptFromJson(json);

  Map<String, dynamic> toJson() => _$VendorSettlementReceiptToJson(this);

  @override
  String toString() {
    return toJson().toString();
  }
}

enum VendorSettlementReceiptMethodEnum {
  @JsonValue(r'school_internal')
  schoolInternal(r'school_internal'),
  @JsonValue(r'cash')
  cash(r'cash'),
  @JsonValue(r'eft')
  eft(r'eft'),
  @JsonValue(r'unknown_default_open_api')
  unknownDefaultOpenApi(r'unknown_default_open_api');

  const VendorSettlementReceiptMethodEnum(this.value);

  final String value;

  @override
  String toString() => value;
}

enum VendorSettlementReceiptCollectedByEnum {
  @JsonValue(r'school')
  school(r'school'),
  @JsonValue(r'unknown_default_open_api')
  unknownDefaultOpenApi(r'unknown_default_open_api');

  const VendorSettlementReceiptCollectedByEnum(this.value);

  final String value;

  @override
  String toString() => value;
}

enum VendorSettlementReceiptStatusEnum {
  @JsonValue(r'prepared')
  prepared(r'prepared'),
  @JsonValue(r'approved')
  approved(r'approved'),
  @JsonValue(r'settled')
  settled(r'settled'),
  @JsonValue(r'unknown_default_open_api')
  unknownDefaultOpenApi(r'unknown_default_open_api');

  const VendorSettlementReceiptStatusEnum(this.value);

  final String value;

  @override
  String toString() => value;
}
