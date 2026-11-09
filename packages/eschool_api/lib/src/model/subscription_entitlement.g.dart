// GENERATED CODE - DO NOT MODIFY BY HAND

part of 'subscription_entitlement.dart';

// **************************************************************************
// JsonSerializableGenerator
// **************************************************************************

SubscriptionEntitlement _$SubscriptionEntitlementFromJson(
  Map<String, dynamic> json,
) => $checkedCreate('SubscriptionEntitlement', json, ($checkedConvert) {
  $checkKeys(
    json,
    requiredKeys: const ['required', 'entitled', 'source', 'until'],
  );
  final val = SubscriptionEntitlement(
    required_: $checkedConvert('required', (v) => v as bool),
    entitled: $checkedConvert('entitled', (v) => v as bool),
    source_: $checkedConvert(
      'source',
      (v) => $enumDecodeNullable(
        _$SubscriptionEntitlementSource_EnumEnumMap,
        v,
        unknownValue: SubscriptionEntitlementSource_Enum.unknownDefaultOpenApi,
      ),
    ),
    until: $checkedConvert(
      'until',
      (v) => v == null ? null : DateTime.parse(v as String),
    ),
  );
  return val;
}, fieldKeyMap: const {'required_': 'required', 'source_': 'source'});

Map<String, dynamic> _$SubscriptionEntitlementToJson(
  SubscriptionEntitlement instance,
) => <String, dynamic>{
  'required': instance.required_,
  'entitled': instance.entitled,
  'source': _$SubscriptionEntitlementSource_EnumEnumMap[instance.source_],
  'until': instance.until?.toIso8601String(),
};

const _$SubscriptionEntitlementSource_EnumEnumMap = {
  SubscriptionEntitlementSource_Enum.included: 'included',
  SubscriptionEntitlementSource_Enum.schoolLicence: 'school_licence',
  SubscriptionEntitlementSource_Enum.sponsored: 'sponsored',
  SubscriptionEntitlementSource_Enum.subscription: 'subscription',
  SubscriptionEntitlementSource_Enum.unknownDefaultOpenApi:
      'unknown_default_open_api',
};
