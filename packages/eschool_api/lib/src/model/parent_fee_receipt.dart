//
// AUTO-GENERATED FILE, DO NOT MODIFY!
//

// ignore_for_file: unused_element
import 'package:json_annotation/json_annotation.dart';

part 'parent_fee_receipt.g.dart';

@JsonSerializable(
  checked: true,
  createToJson: true,
  disallowUnrecognizedKeys: false,
  explicitToJson: true,
)
class ParentFeeReceipt {
  /// Returns a new [ParentFeeReceipt] instance.
  ParentFeeReceipt({
    required this.id,

    required this.number,

    required this.pinnedLearnerId,

    required this.method,

    required this.receivedOn,

    required this.currency,

    required this.amountCents,

    required this.allocatedCents,

    required this.creditCents,

    this.releasedCents,

    this.pendingRefundCents,

    this.refundedCents,
  });

  // minimum: 1
  @JsonKey(name: r'id', required: true, includeIfNull: false)
  final int id;

  @JsonKey(name: r'number', required: true, includeIfNull: false)
  final String number;

  // minimum: 1
  @JsonKey(name: r'pinned_learner_id', required: true, includeIfNull: true)
  final int? pinnedLearnerId;

  @JsonKey(
    name: r'method',
    required: true,
    includeIfNull: false,
    unknownEnumValue: ParentFeeReceiptMethodEnum.unknownDefaultOpenApi,
  )
  final ParentFeeReceiptMethodEnum method;

  @JsonKey(name: r'received_on', required: true, includeIfNull: false)
  final String receivedOn;

  @JsonKey(name: r'currency', required: true, includeIfNull: false)
  final String currency;

  // minimum: 1
  @JsonKey(name: r'amount_cents', required: true, includeIfNull: false)
  final int amountCents;

  // minimum: 0
  @JsonKey(name: r'allocated_cents', required: true, includeIfNull: false)
  final int allocatedCents;

  // minimum: 0
  @JsonKey(name: r'credit_cents', required: true, includeIfNull: false)
  final int creditCents;

  /// Cumulative money restored to this receipt by posted credit notes; current held money remains credit_cents.
  // minimum: 0
  @JsonKey(name: r'released_cents', required: false, includeIfNull: false)
  final int? releasedCents;

  /// Original receipt credit reserved for an approved manual refund; unavailable for allocation.
  // minimum: 0
  @JsonKey(name: r'pending_refund_cents', required: false, includeIfNull: false)
  final int? pendingRefundCents;

  /// Original receipt amount already paid back to its holder by verified manual refund.
  // minimum: 0
  @JsonKey(name: r'refunded_cents', required: false, includeIfNull: false)
  final int? refundedCents;

  @override
  bool operator ==(Object other) =>
      identical(this, other) ||
      other is ParentFeeReceipt &&
          other.id == id &&
          other.number == number &&
          other.pinnedLearnerId == pinnedLearnerId &&
          other.method == method &&
          other.receivedOn == receivedOn &&
          other.currency == currency &&
          other.amountCents == amountCents &&
          other.allocatedCents == allocatedCents &&
          other.creditCents == creditCents &&
          other.releasedCents == releasedCents &&
          other.pendingRefundCents == pendingRefundCents &&
          other.refundedCents == refundedCents;

  @override
  int get hashCode =>
      id.hashCode +
      number.hashCode +
      (pinnedLearnerId == null ? 0 : pinnedLearnerId.hashCode) +
      method.hashCode +
      receivedOn.hashCode +
      currency.hashCode +
      amountCents.hashCode +
      allocatedCents.hashCode +
      creditCents.hashCode +
      releasedCents.hashCode +
      pendingRefundCents.hashCode +
      refundedCents.hashCode;

  factory ParentFeeReceipt.fromJson(Map<String, dynamic> json) =>
      _$ParentFeeReceiptFromJson(json);

  Map<String, dynamic> toJson() => _$ParentFeeReceiptToJson(this);

  @override
  String toString() {
    return toJson().toString();
  }
}

enum ParentFeeReceiptMethodEnum {
  @JsonValue(r'cash')
  cash(r'cash'),
  @JsonValue(r'eft')
  eft(r'eft'),
  @JsonValue(r'unknown_default_open_api')
  unknownDefaultOpenApi(r'unknown_default_open_api');

  const ParentFeeReceiptMethodEnum(this.value);

  final String value;

  @override
  String toString() => value;
}
