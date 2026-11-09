// GENERATED CODE - DO NOT MODIFY BY HAND

part of 'giving_campaign_list_items_inner.dart';

// **************************************************************************
// JsonSerializableGenerator
// **************************************************************************

GivingCampaignListItemsInner _$GivingCampaignListItemsInnerFromJson(
  Map<String, dynamic> json,
) => $checkedCreate(
  'GivingCampaignListItemsInner',
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
        'starts_on',
        'ends_on',
        'public_totals',
        'state',
        'revision',
        'raised_cents',
        'pledged_cents',
        'donations',
      ],
    );
    final val = GivingCampaignListItemsInner(
      id: $checkedConvert('id', (v) => (v as num).toInt()),
      name: $checkedConvert('name', (v) => v as String),
      description: $checkedConvert('description', (v) => v as String?),
      currency: $checkedConvert('currency', (v) => v as String),
      goalCents: $checkedConvert('goal_cents', (v) => (v as num?)?.toInt()),
      startsOn: $checkedConvert(
        'starts_on',
        (v) => DateTime.parse(v as String),
      ),
      endsOn: $checkedConvert('ends_on', (v) => DateTime.parse(v as String)),
      publicTotals: $checkedConvert('public_totals', (v) => v as bool),
      state: $checkedConvert(
        'state',
        (v) => $enumDecode(
          _$GivingCampaignListItemsInnerStateEnumEnumMap,
          v,
          unknownValue:
              GivingCampaignListItemsInnerStateEnum.unknownDefaultOpenApi,
        ),
      ),
      revision: $checkedConvert('revision', (v) => (v as num).toInt()),
      raisedCents: $checkedConvert('raised_cents', (v) => (v as num).toInt()),
      pledgedCents: $checkedConvert('pledged_cents', (v) => (v as num).toInt()),
      donations: $checkedConvert('donations', (v) => (v as num).toInt()),
    );
    return val;
  },
  fieldKeyMap: const {
    'goalCents': 'goal_cents',
    'startsOn': 'starts_on',
    'endsOn': 'ends_on',
    'publicTotals': 'public_totals',
    'raisedCents': 'raised_cents',
    'pledgedCents': 'pledged_cents',
  },
);

Map<String, dynamic> _$GivingCampaignListItemsInnerToJson(
  GivingCampaignListItemsInner instance,
) => <String, dynamic>{
  'id': instance.id,
  'name': instance.name,
  'description': instance.description,
  'currency': instance.currency,
  'goal_cents': instance.goalCents,
  'starts_on': instance.startsOn.toIso8601String(),
  'ends_on': instance.endsOn.toIso8601String(),
  'public_totals': instance.publicTotals,
  'state': _$GivingCampaignListItemsInnerStateEnumEnumMap[instance.state]!,
  'revision': instance.revision,
  'raised_cents': instance.raisedCents,
  'pledged_cents': instance.pledgedCents,
  'donations': instance.donations,
};

const _$GivingCampaignListItemsInnerStateEnumEnumMap = {
  GivingCampaignListItemsInnerStateEnum.open: 'open',
  GivingCampaignListItemsInnerStateEnum.closed: 'closed',
  GivingCampaignListItemsInnerStateEnum.unknownDefaultOpenApi:
      'unknown_default_open_api',
};
