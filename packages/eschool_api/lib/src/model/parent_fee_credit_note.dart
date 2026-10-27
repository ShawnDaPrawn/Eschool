//
// AUTO-GENERATED FILE, DO NOT MODIFY!
//

// ignore_for_file: unused_element
import 'package:json_annotation/json_annotation.dart';

part 'parent_fee_credit_note.g.dart';

@JsonSerializable(
  checked: true,
  createToJson: true,
  disallowUnrecognizedKeys: false,
  explicitToJson: true,
)
class ParentFeeCreditNote {
  /// Returns a new [ParentFeeCreditNote] instance.
  ParentFeeCreditNote({
    required this.id,

    required this.number,

    required this.invoiceLineId,

    required this.invoiceNumber,

    required this.learnerId,

    required this.learnerName,

    required this.currency,

    required this.amountCents,

    required this.unpaidCents,

    required this.releasedCents,

    required this.reasonCode,

    required this.issuedOn,
  });

  // minimum: 1
  @JsonKey(name: r'id', required: true, includeIfNull: false)
  final int id;

  @JsonKey(name: r'number', required: true, includeIfNull: false)
  final String number;

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

  @JsonKey(name: r'currency', required: true, includeIfNull: false)
  final String currency;

  // minimum: 1
  @JsonKey(name: r'amount_cents', required: true, includeIfNull: false)
  final int amountCents;

  // minimum: 0
  @JsonKey(name: r'unpaid_cents', required: true, includeIfNull: false)
  final int unpaidCents;

  // minimum: 0
  @JsonKey(name: r'released_cents', required: true, includeIfNull: false)
  final int releasedCents;

  @JsonKey(
    name: r'reason_code',
    required: true,
    includeIfNull: false,
    unknownEnumValue: ParentFeeCreditNoteReasonCodeEnum.unknownDefaultOpenApi,
  )
  final ParentFeeCreditNoteReasonCodeEnum reasonCode;

  @JsonKey(name: r'issued_on', required: true, includeIfNull: false)
  final String issuedOn;

  @override
  bool operator ==(Object other) =>
      identical(this, other) ||
      other is ParentFeeCreditNote &&
          other.id == id &&
          other.number == number &&
          other.invoiceLineId == invoiceLineId &&
          other.invoiceNumber == invoiceNumber &&
          other.learnerId == learnerId &&
          other.learnerName == learnerName &&
          other.currency == currency &&
          other.amountCents == amountCents &&
          other.unpaidCents == unpaidCents &&
          other.releasedCents == releasedCents &&
          other.reasonCode == reasonCode &&
          other.issuedOn == issuedOn;

  @override
  int get hashCode =>
      id.hashCode +
      number.hashCode +
      invoiceLineId.hashCode +
      invoiceNumber.hashCode +
      learnerId.hashCode +
      learnerName.hashCode +
      currency.hashCode +
      amountCents.hashCode +
      unpaidCents.hashCode +
      releasedCents.hashCode +
      reasonCode.hashCode +
      issuedOn.hashCode;

  factory ParentFeeCreditNote.fromJson(Map<String, dynamic> json) =>
      _$ParentFeeCreditNoteFromJson(json);

  Map<String, dynamic> toJson() => _$ParentFeeCreditNoteToJson(this);

  @override
  String toString() {
    return toJson().toString();
  }
}

enum ParentFeeCreditNoteReasonCodeEnum {
  @JsonValue(r'billing_error')
  billingError(r'billing_error'),
  @JsonValue(r'fee_reduction')
  feeReduction(r'fee_reduction'),
  @JsonValue(r'leaver_credit')
  leaverCredit(r'leaver_credit'),
  @JsonValue(r'other')
  other(r'other'),
  @JsonValue(r'unknown_default_open_api')
  unknownDefaultOpenApi(r'unknown_default_open_api');

  const ParentFeeCreditNoteReasonCodeEnum(this.value);

  final String value;

  @override
  String toString() => value;
}
