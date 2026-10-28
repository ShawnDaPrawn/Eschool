//
// AUTO-GENERATED FILE, DO NOT MODIFY!
//

// ignore_for_file: unused_element
import 'package:json_annotation/json_annotation.dart';

part 'vendor_settlement_summary.g.dart';

@JsonSerializable(
  checked: true,
  createToJson: true,
  disallowUnrecognizedKeys: false,
  explicitToJson: true,
)
class VendorSettlementSummary {
  /// Returns a new [VendorSettlementSummary] instance.
  VendorSettlementSummary({
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
  });

  // minimum: 1
  // maximum: 100000000000
  @JsonKey(name: r'id', required: true, includeIfNull: false)
  final int id;

  @JsonKey(
    name: r'method',
    required: true,
    includeIfNull: false,
    unknownEnumValue: VendorSettlementSummaryMethodEnum.unknownDefaultOpenApi,
  )
  final VendorSettlementSummaryMethodEnum method;

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
        VendorSettlementSummaryCollectedByEnum.unknownDefaultOpenApi,
  )
  final VendorSettlementSummaryCollectedByEnum collectedBy;

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
    unknownEnumValue: VendorSettlementSummaryStatusEnum.unknownDefaultOpenApi,
  )
  final VendorSettlementSummaryStatusEnum status;

  @JsonKey(name: r'created_at', required: true, includeIfNull: false)
  final DateTime createdAt;

  @JsonKey(name: r'settled_at', required: true, includeIfNull: true)
  final DateTime? settledAt;

  @JsonKey(name: r'paid_on', required: true, includeIfNull: true)
  final DateTime? paidOn;

  @override
  bool operator ==(Object other) =>
      identical(this, other) ||
      other is VendorSettlementSummary &&
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
          other.paidOn == paidOn;

  @override
  int get hashCode =>
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
      (paidOn == null ? 0 : paidOn.hashCode);

  factory VendorSettlementSummary.fromJson(Map<String, dynamic> json) =>
      _$VendorSettlementSummaryFromJson(json);

  Map<String, dynamic> toJson() => _$VendorSettlementSummaryToJson(this);

  @override
  String toString() {
    return toJson().toString();
  }
}

enum VendorSettlementSummaryMethodEnum {
  @JsonValue(r'school_internal')
  schoolInternal(r'school_internal'),
  @JsonValue(r'cash')
  cash(r'cash'),
  @JsonValue(r'eft')
  eft(r'eft'),
  @JsonValue(r'unknown_default_open_api')
  unknownDefaultOpenApi(r'unknown_default_open_api');

  const VendorSettlementSummaryMethodEnum(this.value);

  final String value;

  @override
  String toString() => value;
}

enum VendorSettlementSummaryCollectedByEnum {
  @JsonValue(r'school')
  school(r'school'),
  @JsonValue(r'unknown_default_open_api')
  unknownDefaultOpenApi(r'unknown_default_open_api');

  const VendorSettlementSummaryCollectedByEnum(this.value);

  final String value;

  @override
  String toString() => value;
}

enum VendorSettlementSummaryStatusEnum {
  @JsonValue(r'prepared')
  prepared(r'prepared'),
  @JsonValue(r'approved')
  approved(r'approved'),
  @JsonValue(r'settled')
  settled(r'settled'),
  @JsonValue(r'unknown_default_open_api')
  unknownDefaultOpenApi(r'unknown_default_open_api');

  const VendorSettlementSummaryStatusEnum(this.value);

  final String value;

  @override
  String toString() => value;
}
