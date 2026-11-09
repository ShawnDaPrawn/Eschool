//
// AUTO-GENERATED FILE, DO NOT MODIFY!
//

// ignore_for_file: unused_element
import 'package:json_annotation/json_annotation.dart';

part 'giving_receipt.g.dart';

@JsonSerializable(
  checked: true,
  createToJson: true,
  disallowUnrecognizedKeys: false,
  explicitToJson: true,
)
class GivingReceipt {
  /// Returns a new [GivingReceipt] instance.
  GivingReceipt({
    required this.id,

    required this.receipt,

    required this.donorName,

    required this.donorEmail,

    required this.amountCents,

    required this.currency,

    required this.method,

    required this.sourceReference,

    required this.receivedOn,

    required this.pledgeId,

    required this.ledgerEntryId,

    required this.campaign,

    required this.school,

    required this.issuedAt,
  });

  // minimum: 1
  @JsonKey(name: r'id', required: true, includeIfNull: false)
  final int id;

  @JsonKey(name: r'receipt', required: true, includeIfNull: false)
  final String receipt;

  @JsonKey(name: r'donor_name', required: true, includeIfNull: false)
  final String donorName;

  @JsonKey(name: r'donor_email', required: true, includeIfNull: true)
  final String? donorEmail;

  // minimum: 1
  @JsonKey(name: r'amount_cents', required: true, includeIfNull: false)
  final int amountCents;

  @JsonKey(name: r'currency', required: true, includeIfNull: false)
  final String currency;

  @JsonKey(name: r'method', required: true, includeIfNull: false)
  final String method;

  @JsonKey(name: r'source_reference', required: true, includeIfNull: false)
  final String sourceReference;

  @JsonKey(name: r'received_on', required: true, includeIfNull: false)
  final DateTime receivedOn;

  @JsonKey(name: r'pledge_id', required: true, includeIfNull: true)
  final int? pledgeId;

  // minimum: 1
  @JsonKey(name: r'ledger_entry_id', required: true, includeIfNull: false)
  final int ledgerEntryId;

  @JsonKey(name: r'campaign', required: true, includeIfNull: false)
  final String campaign;

  @JsonKey(name: r'school', required: true, includeIfNull: false)
  final String school;

  @JsonKey(name: r'issued_at', required: true, includeIfNull: false)
  final DateTime issuedAt;

  @override
  bool operator ==(Object other) =>
      identical(this, other) ||
      other is GivingReceipt &&
          other.id == id &&
          other.receipt == receipt &&
          other.donorName == donorName &&
          other.donorEmail == donorEmail &&
          other.amountCents == amountCents &&
          other.currency == currency &&
          other.method == method &&
          other.sourceReference == sourceReference &&
          other.receivedOn == receivedOn &&
          other.pledgeId == pledgeId &&
          other.ledgerEntryId == ledgerEntryId &&
          other.campaign == campaign &&
          other.school == school &&
          other.issuedAt == issuedAt;

  @override
  int get hashCode =>
      id.hashCode +
      receipt.hashCode +
      donorName.hashCode +
      (donorEmail == null ? 0 : donorEmail.hashCode) +
      amountCents.hashCode +
      currency.hashCode +
      method.hashCode +
      sourceReference.hashCode +
      receivedOn.hashCode +
      (pledgeId == null ? 0 : pledgeId.hashCode) +
      ledgerEntryId.hashCode +
      campaign.hashCode +
      school.hashCode +
      issuedAt.hashCode;

  factory GivingReceipt.fromJson(Map<String, dynamic> json) =>
      _$GivingReceiptFromJson(json);

  Map<String, dynamic> toJson() => _$GivingReceiptToJson(this);

  @override
  String toString() {
    return toJson().toString();
  }
}
