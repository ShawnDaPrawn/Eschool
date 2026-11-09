//
// AUTO-GENERATED FILE, DO NOT MODIFY!
//

// ignore_for_file: unused_element
import 'package:json_annotation/json_annotation.dart';

part 'giving_campaign_summary.g.dart';

@JsonSerializable(
  checked: true,
  createToJson: true,
  disallowUnrecognizedKeys: false,
  explicitToJson: true,
)
class GivingCampaignSummary {
  /// Returns a new [GivingCampaignSummary] instance.
  GivingCampaignSummary({
    required this.id,

    required this.name,

    required this.description,

    required this.currency,

    required this.goalCents,

    required this.state,

    required this.raisedCents,

    required this.donations,
  });

  // minimum: 1
  @JsonKey(name: r'id', required: true, includeIfNull: false)
  final int id;

  @JsonKey(name: r'name', required: true, includeIfNull: false)
  final String name;

  @JsonKey(name: r'description', required: true, includeIfNull: true)
  final String? description;

  @JsonKey(name: r'currency', required: true, includeIfNull: false)
  final String currency;

  @JsonKey(name: r'goal_cents', required: true, includeIfNull: true)
  final int? goalCents;

  @JsonKey(name: r'state', required: true, includeIfNull: false)
  final String state;

  @JsonKey(name: r'raised_cents', required: true, includeIfNull: false)
  final int raisedCents;

  @JsonKey(name: r'donations', required: true, includeIfNull: false)
  final int donations;

  @override
  bool operator ==(Object other) =>
      identical(this, other) ||
      other is GivingCampaignSummary &&
          other.id == id &&
          other.name == name &&
          other.description == description &&
          other.currency == currency &&
          other.goalCents == goalCents &&
          other.state == state &&
          other.raisedCents == raisedCents &&
          other.donations == donations;

  @override
  int get hashCode =>
      id.hashCode +
      name.hashCode +
      (description == null ? 0 : description.hashCode) +
      currency.hashCode +
      (goalCents == null ? 0 : goalCents.hashCode) +
      state.hashCode +
      raisedCents.hashCode +
      donations.hashCode;

  factory GivingCampaignSummary.fromJson(Map<String, dynamic> json) =>
      _$GivingCampaignSummaryFromJson(json);

  Map<String, dynamic> toJson() => _$GivingCampaignSummaryToJson(this);

  @override
  String toString() {
    return toJson().toString();
  }
}
