// GENERATED CODE - DO NOT MODIFY BY HAND

part of 'giving_campaign_list.dart';

// **************************************************************************
// JsonSerializableGenerator
// **************************************************************************

GivingCampaignList _$GivingCampaignListFromJson(Map<String, dynamic> json) =>
    $checkedCreate('GivingCampaignList', json, ($checkedConvert) {
      $checkKeys(json, requiredKeys: const ['items']);
      final val = GivingCampaignList(
        items: $checkedConvert(
          'items',
          (v) => (v as List<dynamic>)
              .map(
                (e) => GivingCampaignListItemsInner.fromJson(
                  e as Map<String, dynamic>,
                ),
              )
              .toList(),
        ),
      );
      return val;
    });

Map<String, dynamic> _$GivingCampaignListToJson(GivingCampaignList instance) =>
    <String, dynamic>{'items': instance.items.map((e) => e.toJson()).toList()};
