// GENERATED CODE - DO NOT MODIFY BY HAND

part of 'subscription.dart';

// **************************************************************************
// JsonSerializableGenerator
// **************************************************************************

Subscription _$SubscriptionFromJson(
  Map<String, dynamic> json,
) => $checkedCreate(
  'Subscription',
  json,
  ($checkedConvert) {
    $checkKeys(
      json,
      requiredKeys: const [
        'id',
        'reference',
        'plan',
        'status',
        'effective_status',
        'learner_id',
        'price_cents',
        'currency',
        'period_months',
        'current_period_end',
        'requested_on',
        'can_cancel',
      ],
    );
    final val = Subscription(
      id: $checkedConvert('id', (v) => (v as num).toInt()),
      reference: $checkedConvert('reference', (v) => v as String),
      plan: $checkedConvert(
        'plan',
        (v) => $enumDecode(
          _$SubscriptionPlanEnumEnumMap,
          v,
          unknownValue: SubscriptionPlanEnum.unknownDefaultOpenApi,
        ),
      ),
      status: $checkedConvert(
        'status',
        (v) => $enumDecode(
          _$SubscriptionStatusEnumEnumMap,
          v,
          unknownValue: SubscriptionStatusEnum.unknownDefaultOpenApi,
        ),
      ),
      effectiveStatus: $checkedConvert(
        'effective_status',
        (v) => $enumDecode(
          _$SubscriptionEffectiveStatusEnumEnumMap,
          v,
          unknownValue: SubscriptionEffectiveStatusEnum.unknownDefaultOpenApi,
        ),
      ),
      learnerId: $checkedConvert('learner_id', (v) => (v as num?)?.toInt()),
      priceCents: $checkedConvert('price_cents', (v) => (v as num).toInt()),
      currency: $checkedConvert('currency', (v) => v as String),
      periodMonths: $checkedConvert('period_months', (v) => (v as num).toInt()),
      currentPeriodEnd: $checkedConvert(
        'current_period_end',
        (v) => v == null ? null : DateTime.parse(v as String),
      ),
      requestedOn: $checkedConvert(
        'requested_on',
        (v) => v == null ? null : DateTime.parse(v as String),
      ),
      canCancel: $checkedConvert('can_cancel', (v) => v as bool),
    );
    return val;
  },
  fieldKeyMap: const {
    'effectiveStatus': 'effective_status',
    'learnerId': 'learner_id',
    'priceCents': 'price_cents',
    'periodMonths': 'period_months',
    'currentPeriodEnd': 'current_period_end',
    'requestedOn': 'requested_on',
    'canCancel': 'can_cancel',
  },
);

Map<String, dynamic> _$SubscriptionToJson(Subscription instance) =>
    <String, dynamic>{
      'id': instance.id,
      'reference': instance.reference,
      'plan': _$SubscriptionPlanEnumEnumMap[instance.plan]!,
      'status': _$SubscriptionStatusEnumEnumMap[instance.status]!,
      'effective_status':
          _$SubscriptionEffectiveStatusEnumEnumMap[instance.effectiveStatus]!,
      'learner_id': instance.learnerId,
      'price_cents': instance.priceCents,
      'currency': instance.currency,
      'period_months': instance.periodMonths,
      'current_period_end': instance.currentPeriodEnd?.toIso8601String(),
      'requested_on': instance.requestedOn?.toIso8601String(),
      'can_cancel': instance.canCancel,
    };

const _$SubscriptionPlanEnumEnumMap = {
  SubscriptionPlanEnum.perChild: 'per_child',
  SubscriptionPlanEnum.family: 'family',
  SubscriptionPlanEnum.schoolLicence: 'school_licence',
  SubscriptionPlanEnum.sponsored: 'sponsored',
  SubscriptionPlanEnum.unknownDefaultOpenApi: 'unknown_default_open_api',
};

const _$SubscriptionStatusEnumEnumMap = {
  SubscriptionStatusEnum.requested: 'requested',
  SubscriptionStatusEnum.active: 'active',
  SubscriptionStatusEnum.cancelled: 'cancelled',
  SubscriptionStatusEnum.ended: 'ended',
  SubscriptionStatusEnum.declined: 'declined',
  SubscriptionStatusEnum.unknownDefaultOpenApi: 'unknown_default_open_api',
};

const _$SubscriptionEffectiveStatusEnumEnumMap = {
  SubscriptionEffectiveStatusEnum.requested: 'requested',
  SubscriptionEffectiveStatusEnum.active: 'active',
  SubscriptionEffectiveStatusEnum.lapsed: 'lapsed',
  SubscriptionEffectiveStatusEnum.cancelled: 'cancelled',
  SubscriptionEffectiveStatusEnum.ended: 'ended',
  SubscriptionEffectiveStatusEnum.declined: 'declined',
  SubscriptionEffectiveStatusEnum.unknownDefaultOpenApi:
      'unknown_default_open_api',
};
