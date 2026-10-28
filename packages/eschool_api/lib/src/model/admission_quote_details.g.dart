// GENERATED CODE - DO NOT MODIFY BY HAND

part of 'admission_quote_details.dart';

// **************************************************************************
// JsonSerializableGenerator
// **************************************************************************

AdmissionQuoteDetails _$AdmissionQuoteDetailsFromJson(
  Map<String, dynamic> json,
) => $checkedCreate(
  'AdmissionQuoteDetails',
  json,
  ($checkedConvert) {
    $checkKeys(
      json,
      requiredKeys: const [
        'type_id',
        'type_revision',
        'event_id',
        'event_revision',
        'event_name',
        'description',
        'kind',
        'capacity',
        'transfer_rule',
        'starts_at',
        'ends_at',
        'named_learner_id',
      ],
    );
    final val = AdmissionQuoteDetails(
      typeId: $checkedConvert('type_id', (v) => (v as num).toInt()),
      typeRevision: $checkedConvert('type_revision', (v) => (v as num).toInt()),
      eventId: $checkedConvert('event_id', (v) => (v as num).toInt()),
      eventRevision: $checkedConvert(
        'event_revision',
        (v) => (v as num).toInt(),
      ),
      eventName: $checkedConvert('event_name', (v) => v as String),
      description: $checkedConvert('description', (v) => v as String?),
      kind: $checkedConvert(
        'kind',
        (v) => $enumDecode(
          _$AdmissionQuoteDetailsKindEnumEnumMap,
          v,
          unknownValue: AdmissionQuoteDetailsKindEnum.unknownDefaultOpenApi,
        ),
      ),
      capacity: $checkedConvert('capacity', (v) => (v as num).toInt()),
      transferRule: $checkedConvert(
        'transfer_rule',
        (v) => $enumDecode(
          _$AdmissionQuoteDetailsTransferRuleEnumEnumMap,
          v,
          unknownValue:
              AdmissionQuoteDetailsTransferRuleEnum.unknownDefaultOpenApi,
        ),
      ),
      startsAt: $checkedConvert(
        'starts_at',
        (v) => DateTime.parse(v as String),
      ),
      endsAt: $checkedConvert('ends_at', (v) => DateTime.parse(v as String)),
      namedLearnerId: $checkedConvert(
        'named_learner_id',
        (v) => (v as num?)?.toInt(),
      ),
    );
    return val;
  },
  fieldKeyMap: const {
    'typeId': 'type_id',
    'typeRevision': 'type_revision',
    'eventId': 'event_id',
    'eventRevision': 'event_revision',
    'eventName': 'event_name',
    'transferRule': 'transfer_rule',
    'startsAt': 'starts_at',
    'endsAt': 'ends_at',
    'namedLearnerId': 'named_learner_id',
  },
);

Map<String, dynamic> _$AdmissionQuoteDetailsToJson(
  AdmissionQuoteDetails instance,
) => <String, dynamic>{
  'type_id': instance.typeId,
  'type_revision': instance.typeRevision,
  'event_id': instance.eventId,
  'event_revision': instance.eventRevision,
  'event_name': instance.eventName,
  'description': instance.description,
  'kind': _$AdmissionQuoteDetailsKindEnumEnumMap[instance.kind]!,
  'capacity': instance.capacity,
  'transfer_rule':
      _$AdmissionQuoteDetailsTransferRuleEnumEnumMap[instance.transferRule]!,
  'starts_at': instance.startsAt.toIso8601String(),
  'ends_at': instance.endsAt.toIso8601String(),
  'named_learner_id': instance.namedLearnerId,
};

const _$AdmissionQuoteDetailsKindEnumEnumMap = {
  AdmissionQuoteDetailsKindEnum.general: 'general',
  AdmissionQuoteDetailsKindEnum.named: 'named',
  AdmissionQuoteDetailsKindEnum.unknownDefaultOpenApi:
      'unknown_default_open_api',
};

const _$AdmissionQuoteDetailsTransferRuleEnumEnumMap = {
  AdmissionQuoteDetailsTransferRuleEnum.none: 'none',
  AdmissionQuoteDetailsTransferRuleEnum.families: 'families',
  AdmissionQuoteDetailsTransferRuleEnum.walletProfiles: 'wallet_profiles',
  AdmissionQuoteDetailsTransferRuleEnum.unknownDefaultOpenApi:
      'unknown_default_open_api',
};
