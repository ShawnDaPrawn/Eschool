// GENERATED CODE - DO NOT MODIFY BY HAND

part of 'admission_product.dart';

// **************************************************************************
// JsonSerializableGenerator
// **************************************************************************

AdmissionProduct _$AdmissionProductFromJson(
  Map<String, dynamic> json,
) => $checkedCreate(
  'AdmissionProduct',
  json,
  ($checkedConvert) {
    $checkKeys(
      json,
      requiredKeys: const [
        'type_id',
        'event_id',
        'event_name',
        'kind',
        'transfer_rule',
        'remaining_capacity',
        'quantity_limit',
        'can_purchase',
        'starts_at',
        'ends_at',
      ],
    );
    final val = AdmissionProduct(
      typeId: $checkedConvert('type_id', (v) => (v as num).toInt()),
      eventId: $checkedConvert('event_id', (v) => (v as num).toInt()),
      eventName: $checkedConvert('event_name', (v) => v as String),
      kind: $checkedConvert(
        'kind',
        (v) => $enumDecode(
          _$AdmissionProductKindEnumEnumMap,
          v,
          unknownValue: AdmissionProductKindEnum.unknownDefaultOpenApi,
        ),
      ),
      transferRule: $checkedConvert(
        'transfer_rule',
        (v) => $enumDecode(
          _$AdmissionProductTransferRuleEnumEnumMap,
          v,
          unknownValue: AdmissionProductTransferRuleEnum.unknownDefaultOpenApi,
        ),
      ),
      remainingCapacity: $checkedConvert(
        'remaining_capacity',
        (v) => (v as num).toInt(),
      ),
      quantityLimit: $checkedConvert(
        'quantity_limit',
        (v) => (v as num).toInt(),
      ),
      canPurchase: $checkedConvert('can_purchase', (v) => v as bool),
      startsAt: $checkedConvert(
        'starts_at',
        (v) => DateTime.parse(v as String),
      ),
      endsAt: $checkedConvert('ends_at', (v) => DateTime.parse(v as String)),
    );
    return val;
  },
  fieldKeyMap: const {
    'typeId': 'type_id',
    'eventId': 'event_id',
    'eventName': 'event_name',
    'transferRule': 'transfer_rule',
    'remainingCapacity': 'remaining_capacity',
    'quantityLimit': 'quantity_limit',
    'canPurchase': 'can_purchase',
    'startsAt': 'starts_at',
    'endsAt': 'ends_at',
  },
);

Map<String, dynamic> _$AdmissionProductToJson(AdmissionProduct instance) =>
    <String, dynamic>{
      'type_id': instance.typeId,
      'event_id': instance.eventId,
      'event_name': instance.eventName,
      'kind': _$AdmissionProductKindEnumEnumMap[instance.kind]!,
      'transfer_rule':
          _$AdmissionProductTransferRuleEnumEnumMap[instance.transferRule]!,
      'remaining_capacity': instance.remainingCapacity,
      'quantity_limit': instance.quantityLimit,
      'can_purchase': instance.canPurchase,
      'starts_at': instance.startsAt.toIso8601String(),
      'ends_at': instance.endsAt.toIso8601String(),
    };

const _$AdmissionProductKindEnumEnumMap = {
  AdmissionProductKindEnum.general: 'general',
  AdmissionProductKindEnum.named: 'named',
  AdmissionProductKindEnum.unknownDefaultOpenApi: 'unknown_default_open_api',
};

const _$AdmissionProductTransferRuleEnumEnumMap = {
  AdmissionProductTransferRuleEnum.none: 'none',
  AdmissionProductTransferRuleEnum.families: 'families',
  AdmissionProductTransferRuleEnum.walletProfiles: 'wallet_profiles',
  AdmissionProductTransferRuleEnum.unknownDefaultOpenApi:
      'unknown_default_open_api',
};
