// GENERATED CODE - DO NOT MODIFY BY HAND

part of 'create_giving_campaign_request.dart';

// **************************************************************************
// JsonSerializableGenerator
// **************************************************************************

CreateGivingCampaignRequest _$CreateGivingCampaignRequestFromJson(
  Map<String, dynamic> json,
) => $checkedCreate(
  'CreateGivingCampaignRequest',
  json,
  ($checkedConvert) {
    $checkKeys(
      json,
      requiredKeys: const [
        'operation_id',
        'name',
        'currency',
        'starts_on',
        'ends_on',
        'public_totals',
      ],
    );
    final val = CreateGivingCampaignRequest(
      operationId: $checkedConvert('operation_id', (v) => v as String),
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
    );
    return val;
  },
  fieldKeyMap: const {
    'operationId': 'operation_id',
    'goalCents': 'goal_cents',
    'startsOn': 'starts_on',
    'endsOn': 'ends_on',
    'publicTotals': 'public_totals',
  },
);

Map<String, dynamic> _$CreateGivingCampaignRequestToJson(
  CreateGivingCampaignRequest instance,
) => <String, dynamic>{
  'operation_id': instance.operationId,
  'name': instance.name,
  'description': ?instance.description,
  'currency': instance.currency,
  'goal_cents': ?instance.goalCents,
  'starts_on': instance.startsOn.toIso8601String(),
  'ends_on': instance.endsOn.toIso8601String(),
  'public_totals': instance.publicTotals,
};
