//
// AUTO-GENERATED FILE, DO NOT MODIFY!
//

// ignore_for_file: unused_element
import 'package:json_annotation/json_annotation.dart';

part 'giving_donors_pledges_inner.g.dart';

@JsonSerializable(
  checked: true,
  createToJson: true,
  disallowUnrecognizedKeys: false,
  explicitToJson: true,
)
class GivingDonorsPledgesInner {
  /// Returns a new [GivingDonorsPledgesInner] instance.
  GivingDonorsPledgesInner({
    required this.id,

    required this.reference,

    required this.donorName,

    required this.donorEmail,

    required this.amountCents,

    required this.currency,

    required this.pledgedOn,

    required this.receivedCents,
  });

  // minimum: 1
  @JsonKey(name: r'id', required: true, includeIfNull: false)
  final int id;

  @JsonKey(name: r'reference', required: true, includeIfNull: false)
  final String reference;

  @JsonKey(name: r'donor_name', required: true, includeIfNull: false)
  final String donorName;

  @JsonKey(name: r'donor_email', required: true, includeIfNull: true)
  final String? donorEmail;

  // minimum: 1
  @JsonKey(name: r'amount_cents', required: true, includeIfNull: false)
  final int amountCents;

  @JsonKey(name: r'currency', required: true, includeIfNull: false)
  final String currency;

  @JsonKey(name: r'pledged_on', required: true, includeIfNull: false)
  final DateTime pledgedOn;

  @JsonKey(name: r'received_cents', required: true, includeIfNull: false)
  final int receivedCents;

  @override
  bool operator ==(Object other) =>
      identical(this, other) ||
      other is GivingDonorsPledgesInner &&
          other.id == id &&
          other.reference == reference &&
          other.donorName == donorName &&
          other.donorEmail == donorEmail &&
          other.amountCents == amountCents &&
          other.currency == currency &&
          other.pledgedOn == pledgedOn &&
          other.receivedCents == receivedCents;

  @override
  int get hashCode =>
      id.hashCode +
      reference.hashCode +
      donorName.hashCode +
      (donorEmail == null ? 0 : donorEmail.hashCode) +
      amountCents.hashCode +
      currency.hashCode +
      pledgedOn.hashCode +
      receivedCents.hashCode;

  factory GivingDonorsPledgesInner.fromJson(Map<String, dynamic> json) =>
      _$GivingDonorsPledgesInnerFromJson(json);

  Map<String, dynamic> toJson() => _$GivingDonorsPledgesInnerToJson(this);

  @override
  String toString() {
    return toJson().toString();
  }
}
