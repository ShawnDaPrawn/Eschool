// GENERATED CODE - DO NOT MODIFY BY HAND

part of 'subscription_plan.dart';

// **************************************************************************
// JsonSerializableGenerator
// **************************************************************************

SubscriptionPlan _$SubscriptionPlanFromJson(Map<String, dynamic> json) =>
    $checkedCreate(
      'SubscriptionPlan',
      json,
      ($checkedConvert) {
        $checkKeys(
          json,
          requiredKeys: const ['plan', 'price_cents', 'period_months'],
        );
        final val = SubscriptionPlan(
          plan: $checkedConvert(
            'plan',
            (v) => $enumDecode(
              _$SubscriptionPlanPlanEnumEnumMap,
              v,
              unknownValue: SubscriptionPlanPlanEnum.unknownDefaultOpenApi,
            ),
          ),
          priceCents: $checkedConvert('price_cents', (v) => (v as num).toInt()),
          periodMonths: $checkedConvert(
            'period_months',
            (v) => (v as num).toInt(),
          ),
        );
        return val;
      },
      fieldKeyMap: const {
        'priceCents': 'price_cents',
        'periodMonths': 'period_months',
      },
    );

Map<String, dynamic> _$SubscriptionPlanToJson(SubscriptionPlan instance) =>
    <String, dynamic>{
      'plan': _$SubscriptionPlanPlanEnumEnumMap[instance.plan]!,
      'price_cents': instance.priceCents,
      'period_months': instance.periodMonths,
    };

const _$SubscriptionPlanPlanEnumEnumMap = {
  SubscriptionPlanPlanEnum.perChild: 'per_child',
  SubscriptionPlanPlanEnum.family: 'family',
  SubscriptionPlanPlanEnum.unknownDefaultOpenApi: 'unknown_default_open_api',
};
