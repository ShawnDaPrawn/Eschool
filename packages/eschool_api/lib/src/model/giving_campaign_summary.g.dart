// GENERATED CODE - DO NOT MODIFY BY HAND

part of 'giving_campaign_summary.dart';

// **************************************************************************
// JsonSerializableGenerator
// **************************************************************************

GivingCampaignSummary _$GivingCampaignSummaryFromJson(
  Map<String, dynamic> json,
) => $checkedCreate(
  'GivingCampaignSummary',
  json,
  ($checkedConvert) {
    $checkKeys(
      json,
      requiredKeys: const [
        'id',
        'name',
        'description',
        'currency',
        'goal_cents',
        'state',
        'raised_cents',
        'donations',
      ],
    );
    final val = GivingCampaignSummary(
      id: $checkedConvert('id', (v) => (v as num).toInt()),
      name: $checkedConvert('name', (v) => v as String),
      description: $checkedConvert('description', (v) => v as String?),
      currency: $checkedConvert('currency', (v) => v as String),
      goalCents: $checkedConvert('goal_cents', (v) => (v as num?)?.toInt()),
      state: $checkedConvert('state', (v) => v as String),
      raisedCents: $checkedConvert('raised_cents', (v) => (v as num).toInt()),
      donations: $checkedConvert('donations', (v) => (v as num).toInt()),
    );
    return val;
  },
  fieldKeyMap: const {'goalCents': 'goal_cents', 'raisedCents': 'raised_cents'},
);

Map<String, dynamic> _$GivingCampaignSummaryToJson(
  GivingCampaignSummary instance,
) => <String, dynamic>{
  'id': instance.id,
  'name': instance.name,
  'description': instance.description,
  'currency': instance.currency,
  'goal_cents': instance.goalCents,
  'state': instance.state,
  'raised_cents': instance.raisedCents,
  'donations': instance.donations,
};
