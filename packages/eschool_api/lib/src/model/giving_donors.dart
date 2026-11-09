//
// AUTO-GENERATED FILE, DO NOT MODIFY!
//

// ignore_for_file: unused_element
import 'package:eschool_api/src/model/giving_campaign_list_items_inner.dart';
import 'package:eschool_api/src/model/giving_donors_pledges_inner.dart';
import 'package:eschool_api/src/model/giving_donors_donations_inner.dart';
import 'package:json_annotation/json_annotation.dart';

part 'giving_donors.g.dart';

@JsonSerializable(
  checked: true,
  createToJson: true,
  disallowUnrecognizedKeys: false,
  explicitToJson: true,
)
class GivingDonors {
  /// Returns a new [GivingDonors] instance.
  GivingDonors({
    required this.campaign,

    required this.donations,

    required this.pledges,
  });

  @JsonKey(name: r'campaign', required: true, includeIfNull: false)
  final GivingCampaignListItemsInner campaign;

  @JsonKey(name: r'donations', required: true, includeIfNull: false)
  final List<GivingDonorsDonationsInner> donations;

  @JsonKey(name: r'pledges', required: true, includeIfNull: false)
  final List<GivingDonorsPledgesInner> pledges;

  @override
  bool operator ==(Object other) =>
      identical(this, other) ||
      other is GivingDonors &&
          other.campaign == campaign &&
          other.donations == donations &&
          other.pledges == pledges;

  @override
  int get hashCode => campaign.hashCode + donations.hashCode + pledges.hashCode;

  factory GivingDonors.fromJson(Map<String, dynamic> json) =>
      _$GivingDonorsFromJson(json);

  Map<String, dynamic> toJson() => _$GivingDonorsToJson(this);

  @override
  String toString() {
    return toJson().toString();
  }
}
