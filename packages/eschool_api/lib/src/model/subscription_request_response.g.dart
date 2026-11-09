// GENERATED CODE - DO NOT MODIFY BY HAND

part of 'subscription_request_response.dart';

// **************************************************************************
// JsonSerializableGenerator
// **************************************************************************

SubscriptionRequestResponse _$SubscriptionRequestResponseFromJson(
  Map<String, dynamic> json,
) => $checkedCreate('SubscriptionRequestResponse', json, ($checkedConvert) {
  $checkKeys(json, requiredKeys: const ['error', 'message', 'data', 'code']);
  final val = SubscriptionRequestResponse(
    error: $checkedConvert('error', (v) => v as bool),
    message: $checkedConvert('message', (v) => v as String),
    data: $checkedConvert(
      'data',
      (v) => SubscriptionRequestResult.fromJson(v as Map<String, dynamic>),
    ),
    code: $checkedConvert('code', (v) => (v as num).toInt()),
  );
  return val;
});

Map<String, dynamic> _$SubscriptionRequestResponseToJson(
  SubscriptionRequestResponse instance,
) => <String, dynamic>{
  'error': instance.error,
  'message': instance.message,
  'data': instance.data.toJson(),
  'code': instance.code,
};
