// GENERATED CODE - DO NOT MODIFY BY HAND

part of 'giving_donors.dart';

// **************************************************************************
// JsonSerializableGenerator
// **************************************************************************

GivingDonors _$GivingDonorsFromJson(
  Map<String, dynamic> json,
) => $checkedCreate('GivingDonors', json, ($checkedConvert) {
  $checkKeys(json, requiredKeys: const ['campaign', 'donations', 'pledges']);
  final val = GivingDonors(
    campaign: $checkedConvert(
      'campaign',
      (v) => GivingCampaignListItemsInner.fromJson(v as Map<String, dynamic>),
    ),
    donations: $checkedConvert(
      'donations',
      (v) => (v as List<dynamic>)
          .map(
            (e) =>
                GivingDonorsDonationsInner.fromJson(e as Map<String, dynamic>),
          )
          .toList(),
    ),
    pledges: $checkedConvert(
      'pledges',
      (v) => (v as List<dynamic>)
          .map(
            (e) => GivingDonorsPledgesInner.fromJson(e as Map<String, dynamic>),
          )
          .toList(),
    ),
  );
  return val;
});

Map<String, dynamic> _$GivingDonorsToJson(GivingDonors instance) =>
    <String, dynamic>{
      'campaign': instance.campaign.toJson(),
      'donations': instance.donations.map((e) => e.toJson()).toList(),
      'pledges': instance.pledges.map((e) => e.toJson()).toList(),
    };
