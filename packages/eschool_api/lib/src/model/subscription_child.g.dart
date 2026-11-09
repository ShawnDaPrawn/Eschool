// GENERATED CODE - DO NOT MODIFY BY HAND

part of 'subscription_child.dart';

// **************************************************************************
// JsonSerializableGenerator
// **************************************************************************

SubscriptionChild _$SubscriptionChildFromJson(Map<String, dynamic> json) =>
    $checkedCreate('SubscriptionChild', json, ($checkedConvert) {
      $checkKeys(
        json,
        requiredKeys: const [
          'learner_id',
          'name',
          'approved',
          'entitlement',
          'next_plan',
          'subscription',
        ],
      );
      final val = SubscriptionChild(
        learnerId: $checkedConvert('learner_id', (v) => (v as num).toInt()),
        name: $checkedConvert('name', (v) => v as String),
        approved: $checkedConvert('approved', (v) => v as bool),
        entitlement: $checkedConvert(
          'entitlement',
          (v) => SubscriptionEntitlement.fromJson(v as Map<String, dynamic>),
        ),
        nextPlan: $checkedConvert(
          'next_plan',
          (v) => $enumDecode(
            _$SubscriptionChildNextPlanEnumEnumMap,
            v,
            unknownValue: SubscriptionChildNextPlanEnum.unknownDefaultOpenApi,
          ),
        ),
        subscription: $checkedConvert(
          'subscription',
          (v) => v == null
              ? null
              : Subscription.fromJson(v as Map<String, dynamic>),
        ),
      );
      return val;
    }, fieldKeyMap: const {'learnerId': 'learner_id', 'nextPlan': 'next_plan'});

Map<String, dynamic> _$SubscriptionChildToJson(SubscriptionChild instance) =>
    <String, dynamic>{
      'learner_id': instance.learnerId,
      'name': instance.name,
      'approved': instance.approved,
      'entitlement': instance.entitlement.toJson(),
      'next_plan': _$SubscriptionChildNextPlanEnumEnumMap[instance.nextPlan]!,
      'subscription': instance.subscription?.toJson(),
    };

const _$SubscriptionChildNextPlanEnumEnumMap = {
  SubscriptionChildNextPlanEnum.perChild: 'per_child',
  SubscriptionChildNextPlanEnum.family: 'family',
  SubscriptionChildNextPlanEnum.unknownDefaultOpenApi:
      'unknown_default_open_api',
};
