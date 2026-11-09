// GENERATED CODE - DO NOT MODIFY BY HAND

part of 'subscription_learner_response.dart';

// **************************************************************************
// JsonSerializableGenerator
// **************************************************************************

SubscriptionLearnerResponse _$SubscriptionLearnerResponseFromJson(
  Map<String, dynamic> json,
) => $checkedCreate('SubscriptionLearnerResponse', json, ($checkedConvert) {
  $checkKeys(json, requiredKeys: const ['error', 'message', 'data', 'code']);
  final val = SubscriptionLearnerResponse(
    error: $checkedConvert('error', (v) => v as bool),
    message: $checkedConvert('message', (v) => v as String),
    data: $checkedConvert(
      'data',
      (v) => SubscriptionLearner.fromJson(v as Map<String, dynamic>),
    ),
    code: $checkedConvert('code', (v) => (v as num).toInt()),
  );
  return val;
});

Map<String, dynamic> _$SubscriptionLearnerResponseToJson(
  SubscriptionLearnerResponse instance,
) => <String, dynamic>{
  'error': instance.error,
  'message': instance.message,
  'data': instance.data.toJson(),
  'code': instance.code,
};
