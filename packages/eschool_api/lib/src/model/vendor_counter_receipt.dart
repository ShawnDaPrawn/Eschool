//
// AUTO-GENERATED FILE, DO NOT MODIFY!
//

// ignore_for_file: unused_element
import 'package:eschool_api/src/model/vendor_counter_line.dart';
import 'package:json_annotation/json_annotation.dart';

part 'vendor_counter_receipt.g.dart';

@JsonSerializable(
  checked: true,
  createToJson: true,
  disallowUnrecognizedKeys: false,
  explicitToJson: true,
)
class VendorCounterReceipt {
  /// Returns a new [VendorCounterReceipt] instance.
  VendorCounterReceipt({
    required this.schoolId,

    required this.actorUserId,

    required this.vendorId,

    required this.id,

    required this.orderNumber,

    required this.status,

    required this.currency,

    required this.amountCents,

    required this.netCents,

    required this.vatCents,

    required this.createdAt,

    required this.processedAt,

    required this.canCollect,

    required this.lines,

    this.newlyCollected,
  });

  // minimum: 1
  // maximum: 9000000000000000
  @JsonKey(name: r'school_id', required: true, includeIfNull: false)
  final int schoolId;

  // minimum: 1
  // maximum: 9000000000000000
  @JsonKey(name: r'actor_user_id', required: true, includeIfNull: false)
  final int actorUserId;

  // minimum: 1
  // maximum: 9000000000000000
  @JsonKey(name: r'vendor_id', required: true, includeIfNull: false)
  final int vendorId;

  // minimum: 1
  // maximum: 9000000000000000
  @JsonKey(name: r'id', required: true, includeIfNull: false)
  final int id;

  @JsonKey(name: r'order_number', required: true, includeIfNull: false)
  final String orderNumber;

  @JsonKey(
    name: r'status',
    required: true,
    includeIfNull: false,
    unknownEnumValue: VendorCounterReceiptStatusEnum.unknownDefaultOpenApi,
  )
  final VendorCounterReceiptStatusEnum status;

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

  @JsonKey(name: r'created_at', required: true, includeIfNull: false)
  final DateTime createdAt;

  @JsonKey(name: r'processed_at', required: true, includeIfNull: true)
  final DateTime? processedAt;

  @JsonKey(name: r'can_collect', required: true, includeIfNull: false)
  final bool canCollect;

  @JsonKey(name: r'lines', required: true, includeIfNull: false)
  final List<VendorCounterLine> lines;

  @JsonKey(name: r'newly_collected', required: false, includeIfNull: false)
  final bool? newlyCollected;

  @override
  bool operator ==(Object other) =>
      identical(this, other) ||
      other is VendorCounterReceipt &&
          other.schoolId == schoolId &&
          other.actorUserId == actorUserId &&
          other.vendorId == vendorId &&
          other.id == id &&
          other.orderNumber == orderNumber &&
          other.status == status &&
          other.currency == currency &&
          other.amountCents == amountCents &&
          other.netCents == netCents &&
          other.vatCents == vatCents &&
          other.createdAt == createdAt &&
          other.processedAt == processedAt &&
          other.canCollect == canCollect &&
          other.lines == lines &&
          other.newlyCollected == newlyCollected;

  @override
  int get hashCode =>
      schoolId.hashCode +
      actorUserId.hashCode +
      vendorId.hashCode +
      id.hashCode +
      orderNumber.hashCode +
      status.hashCode +
      currency.hashCode +
      amountCents.hashCode +
      netCents.hashCode +
      vatCents.hashCode +
      createdAt.hashCode +
      (processedAt == null ? 0 : processedAt.hashCode) +
      canCollect.hashCode +
      lines.hashCode +
      newlyCollected.hashCode;

  factory VendorCounterReceipt.fromJson(Map<String, dynamic> json) =>
      _$VendorCounterReceiptFromJson(json);

  Map<String, dynamic> toJson() => _$VendorCounterReceiptToJson(this);

  @override
  String toString() {
    return toJson().toString();
  }
}

enum VendorCounterReceiptStatusEnum {
  @JsonValue(r'ready')
  ready(r'ready'),
  @JsonValue(r'collected')
  collected(r'collected'),
  @JsonValue(r'cancelled')
  cancelled(r'cancelled'),
  @JsonValue(r'unknown_default_open_api')
  unknownDefaultOpenApi(r'unknown_default_open_api');

  const VendorCounterReceiptStatusEnum(this.value);

  final String value;

  @override
  String toString() => value;
}
