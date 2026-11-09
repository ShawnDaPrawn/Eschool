// GENERATED CODE - DO NOT MODIFY BY HAND

part of 'subscription_cancel_result.dart';

// **************************************************************************
// JsonSerializableGenerator
// **************************************************************************

SubscriptionCancelResult _$SubscriptionCancelResultFromJson(
  Map<String, dynamic> json,
) => $checkedCreate(
  'SubscriptionCancelResult',
  json,
  ($checkedConvert) {
    $checkKeys(
      json,
      requiredKeys: const [
        'school_id',
        'reader_id',
        'required',
        'pricing_confirmed',
        'currency',
        'plans',
        'payment_instructions',
        'subscription',
      ],
    );
    final val = SubscriptionCancelResult(
      schoolId: $checkedConvert('school_id', (v) => (v as num).toInt()),
      readerId: $checkedConvert('reader_id', (v) => (v as num).toInt()),
      required_: $checkedConvert('required', (v) => v as bool),
      pricingConfirmed: $checkedConvert('pricing_confirmed', (v) => v as bool),
      currency: $checkedConvert('currency', (v) => v as String),
      plans: $checkedConvert(
        'plans',
        (v) => (v as List<dynamic>)
            .map((e) => SubscriptionPlan.fromJson(e as Map<String, dynamic>))
            .toList(),
      ),
      paymentInstructions: $checkedConvert(
        'payment_instructions',
        (v) => v as String,
      ),
      subscription: $checkedConvert(
        'subscription',
        (v) => Subscription.fromJson(v as Map<String, dynamic>),
      ),
    );
    return val;
  },
  fieldKeyMap: const {
    'schoolId': 'school_id',
    'readerId': 'reader_id',
    'required_': 'required',
    'pricingConfirmed': 'pricing_confirmed',
    'paymentInstructions': 'payment_instructions',
  },
);

Map<String, dynamic> _$SubscriptionCancelResultToJson(
  SubscriptionCancelResult instance,
) => <String, dynamic>{
  'school_id': instance.schoolId,
  'reader_id': instance.readerId,
  'required': instance.required_,
  'pricing_confirmed': instance.pricingConfirmed,
  'currency': instance.currency,
  'plans': instance.plans.map((e) => e.toJson()).toList(),
  'payment_instructions': instance.paymentInstructions,
  'subscription': instance.subscription.toJson(),
};
