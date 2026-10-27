//
// AUTO-GENERATED FILE, DO NOT MODIFY!
//

// ignore_for_file: unused_element
import 'package:json_annotation/json_annotation.dart';

part 'parent_fee_charge.g.dart';

@JsonSerializable(
  checked: true,
  createToJson: true,
  disallowUnrecognizedKeys: false,
  explicitToJson: true,
)
class ParentFeeCharge {
  /// Returns a new [ParentFeeCharge] instance.
  ParentFeeCharge({
    required this.invoiceLineId,

    required this.invoiceNumber,

    required this.learnerId,

    required this.learnerName,

    required this.description,

    required this.periodStart,

    required this.periodEnd,

    required this.issuedOn,

    required this.dueOn,

    required this.currency,

    required this.chargedCents,

    required this.allocatedCents,

    required this.outstandingCents,
  });

  // minimum: 1
  @JsonKey(name: r'invoice_line_id', required: true, includeIfNull: false)
  final int invoiceLineId;

  @JsonKey(name: r'invoice_number', required: true, includeIfNull: false)
  final String invoiceNumber;

  // minimum: 1
  @JsonKey(name: r'learner_id', required: true, includeIfNull: false)
  final int learnerId;

  @JsonKey(name: r'learner_name', required: true, includeIfNull: false)
  final String learnerName;

  @JsonKey(name: r'description', required: true, includeIfNull: false)
  final String description;

  @JsonKey(name: r'period_start', required: true, includeIfNull: false)
  final String periodStart;

  @JsonKey(name: r'period_end', required: true, includeIfNull: false)
  final String periodEnd;

  @JsonKey(name: r'issued_on', required: true, includeIfNull: false)
  final String issuedOn;

  @JsonKey(name: r'due_on', required: true, includeIfNull: false)
  final String dueOn;

  @JsonKey(name: r'currency', required: true, includeIfNull: false)
  final String currency;

  // minimum: 0
  @JsonKey(name: r'charged_cents', required: true, includeIfNull: false)
  final int chargedCents;

  // minimum: 0
  @JsonKey(name: r'allocated_cents', required: true, includeIfNull: false)
  final int allocatedCents;

  // minimum: 0
  @JsonKey(name: r'outstanding_cents', required: true, includeIfNull: false)
  final int outstandingCents;

  @override
  bool operator ==(Object other) =>
      identical(this, other) ||
      other is ParentFeeCharge &&
          other.invoiceLineId == invoiceLineId &&
          other.invoiceNumber == invoiceNumber &&
          other.learnerId == learnerId &&
          other.learnerName == learnerName &&
          other.description == description &&
          other.periodStart == periodStart &&
          other.periodEnd == periodEnd &&
          other.issuedOn == issuedOn &&
          other.dueOn == dueOn &&
          other.currency == currency &&
          other.chargedCents == chargedCents &&
          other.allocatedCents == allocatedCents &&
          other.outstandingCents == outstandingCents;

  @override
  int get hashCode =>
      invoiceLineId.hashCode +
      invoiceNumber.hashCode +
      learnerId.hashCode +
      learnerName.hashCode +
      description.hashCode +
      periodStart.hashCode +
      periodEnd.hashCode +
      issuedOn.hashCode +
      dueOn.hashCode +
      currency.hashCode +
      chargedCents.hashCode +
      allocatedCents.hashCode +
      outstandingCents.hashCode;

  factory ParentFeeCharge.fromJson(Map<String, dynamic> json) =>
      _$ParentFeeChargeFromJson(json);

  Map<String, dynamic> toJson() => _$ParentFeeChargeToJson(this);

  @override
  String toString() {
    return toJson().toString();
  }
}
