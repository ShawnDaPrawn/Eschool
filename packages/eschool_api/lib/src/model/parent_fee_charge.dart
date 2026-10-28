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

    this.chargeKind,

    this.chargeScope,

    required this.periodStart,

    required this.periodEnd,

    required this.issuedOn,

    required this.dueOn,

    required this.currency,

    required this.chargedCents,

    required this.allocatedCents,

    required this.outstandingCents,

    this.grossChargedCents,

    this.creditedCents,

    this.originalVatCents,

    this.vatCreditedCents,

    this.netChargedCents,
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

  /// Optional charge category; missing values retain older-server display.
  @JsonKey(
    name: r'charge_kind',
    required: false,
    includeIfNull: false,
    unknownEnumValue: ParentFeeChargeChargeKindEnum.unknownDefaultOpenApi,
  )
  final ParentFeeChargeChargeKindEnum? chargeKind;

  /// Family items are billed once and apportioned over recorded learners while preserving whole-family holder shares.
  @JsonKey(
    name: r'charge_scope',
    required: false,
    includeIfNull: false,
    unknownEnumValue: ParentFeeChargeChargeScopeEnum.unknownDefaultOpenApi,
  )
  final ParentFeeChargeChargeScopeEnum? chargeScope;

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

  /// Current invoice share after posted credit notes, including applicable VAT; equals allocated plus outstanding cents.
  // minimum: 0
  @JsonKey(name: r'charged_cents', required: true, includeIfNull: false)
  final int chargedCents;

  // minimum: 0
  @JsonKey(name: r'allocated_cents', required: true, includeIfNull: false)
  final int allocatedCents;

  // minimum: 0
  @JsonKey(name: r'outstanding_cents', required: true, includeIfNull: false)
  final int outstandingCents;

  /// Original recorded invoice shares before credit notes. Optional for older servers.
  // minimum: 0
  @JsonKey(name: r'gross_charged_cents', required: false, includeIfNull: false)
  final int? grossChargedCents;

  /// Posted credit notes against these original shares. Optional for older servers.
  // minimum: 0
  @JsonKey(name: r'credited_cents', required: false, includeIfNull: false)
  final int? creditedCents;

  /// Original VAT allocated to this holder and learner share. Optional for older servers.
  // minimum: 0
  @JsonKey(name: r'original_vat_cents', required: false, includeIfNull: false)
  final int? originalVatCents;

  /// VAT reversed by finalized credit notes against this same share.
  // minimum: 0
  @JsonKey(name: r'vat_credited_cents', required: false, includeIfNull: false)
  final int? vatCreditedCents;

  /// Current charged share after credits, excluding its remaining VAT.
  // minimum: 0
  @JsonKey(name: r'net_charged_cents', required: false, includeIfNull: false)
  final int? netChargedCents;

  @override
  bool operator ==(Object other) =>
      identical(this, other) ||
      other is ParentFeeCharge &&
          other.invoiceLineId == invoiceLineId &&
          other.invoiceNumber == invoiceNumber &&
          other.learnerId == learnerId &&
          other.learnerName == learnerName &&
          other.description == description &&
          other.chargeKind == chargeKind &&
          other.chargeScope == chargeScope &&
          other.periodStart == periodStart &&
          other.periodEnd == periodEnd &&
          other.issuedOn == issuedOn &&
          other.dueOn == dueOn &&
          other.currency == currency &&
          other.chargedCents == chargedCents &&
          other.allocatedCents == allocatedCents &&
          other.outstandingCents == outstandingCents &&
          other.grossChargedCents == grossChargedCents &&
          other.creditedCents == creditedCents &&
          other.originalVatCents == originalVatCents &&
          other.vatCreditedCents == vatCreditedCents &&
          other.netChargedCents == netChargedCents;

  @override
  int get hashCode =>
      invoiceLineId.hashCode +
      invoiceNumber.hashCode +
      learnerId.hashCode +
      learnerName.hashCode +
      description.hashCode +
      chargeKind.hashCode +
      chargeScope.hashCode +
      periodStart.hashCode +
      periodEnd.hashCode +
      issuedOn.hashCode +
      dueOn.hashCode +
      currency.hashCode +
      chargedCents.hashCode +
      allocatedCents.hashCode +
      outstandingCents.hashCode +
      grossChargedCents.hashCode +
      creditedCents.hashCode +
      originalVatCents.hashCode +
      vatCreditedCents.hashCode +
      netChargedCents.hashCode;

  factory ParentFeeCharge.fromJson(Map<String, dynamic> json) =>
      _$ParentFeeChargeFromJson(json);

  Map<String, dynamic> toJson() => _$ParentFeeChargeToJson(this);

  @override
  String toString() {
    return toJson().toString();
  }
}

/// Optional charge category; missing values retain older-server display.
enum ParentFeeChargeChargeKindEnum {
  @JsonValue(r'annual_tuition')
  annualTuition(r'annual_tuition'),
  @JsonValue(r'fee_item')
  feeItem(r'fee_item'),
  @JsonValue(r'unknown_default_open_api')
  unknownDefaultOpenApi(r'unknown_default_open_api');

  const ParentFeeChargeChargeKindEnum(this.value);

  final String value;

  @override
  String toString() => value;
}

/// Family items are billed once and apportioned over recorded learners while preserving whole-family holder shares.
enum ParentFeeChargeChargeScopeEnum {
  @JsonValue(r'learner')
  learner(r'learner'),
  @JsonValue(r'family')
  family(r'family'),
  @JsonValue(r'unknown_default_open_api')
  unknownDefaultOpenApi(r'unknown_default_open_api');

  const ParentFeeChargeChargeScopeEnum(this.value);

  final String value;

  @override
  String toString() => value;
}
