// GENERATED CODE - DO NOT MODIFY BY HAND

part of 'giving_campaign_change.dart';

// **************************************************************************
// JsonSerializableGenerator
// **************************************************************************

GivingCampaignChange _$GivingCampaignChangeFromJson(
  Map<String, dynamic> json,
) => $checkedCreate('GivingCampaignChange', json, ($checkedConvert) {
  $checkKeys(json, requiredKeys: const ['id', 'state', 'revision']);
  final val = GivingCampaignChange(
    id: $checkedConvert('id', (v) => (v as num).toInt()),
    state: $checkedConvert('state', (v) => v as String),
    revision: $checkedConvert('revision', (v) => (v as num).toInt()),
  );
  return val;
});

Map<String, dynamic> _$GivingCampaignChangeToJson(
  GivingCampaignChange instance,
) => <String, dynamic>{
  'id': instance.id,
  'state': instance.state,
  'revision': instance.revision,
};
