// GENERATED CODE - DO NOT MODIFY BY HAND

part of 'subscription_cancel_response.dart';

// **************************************************************************
// JsonSerializableGenerator
// **************************************************************************

SubscriptionCancelResponse _$SubscriptionCancelResponseFromJson(
  Map<String, dynamic> json,
) => $checkedCreate('SubscriptionCancelResponse', json, ($checkedConvert) {
  $checkKeys(json, requiredKeys: const ['error', 'message', 'data', 'code']);
  final val = SubscriptionCancelResponse(
    error: $checkedConvert('error', (v) => v as bool),
    message: $checkedConvert('message', (v) => v as String),
    data: $checkedConvert(
      'data',
      (v) => SubscriptionCancelResult.fromJson(v as Map<String, dynamic>),
    ),
    code: $checkedConvert('code', (v) => (v as num).toInt()),
  );
  return val;
});

Map<String, dynamic> _$SubscriptionCancelResponseToJson(
  SubscriptionCancelResponse instance,
) => <String, dynamic>{
  'error': instance.error,
  'message': instance.message,
  'data': instance.data.toJson(),
  'code': instance.code,
};
