// GENERATED CODE - DO NOT MODIFY BY HAND

part of 'subscription_overview_response.dart';

// **************************************************************************
// JsonSerializableGenerator
// **************************************************************************

SubscriptionOverviewResponse _$SubscriptionOverviewResponseFromJson(
  Map<String, dynamic> json,
) => $checkedCreate('SubscriptionOverviewResponse', json, ($checkedConvert) {
  $checkKeys(json, requiredKeys: const ['error', 'message', 'data', 'code']);
  final val = SubscriptionOverviewResponse(
    error: $checkedConvert('error', (v) => v as bool),
    message: $checkedConvert('message', (v) => v as String),
    data: $checkedConvert(
      'data',
      (v) => SubscriptionOverview.fromJson(v as Map<String, dynamic>),
    ),
    code: $checkedConvert('code', (v) => (v as num).toInt()),
  );
  return val;
});

Map<String, dynamic> _$SubscriptionOverviewResponseToJson(
  SubscriptionOverviewResponse instance,
) => <String, dynamic>{
  'error': instance.error,
  'message': instance.message,
  'data': instance.data.toJson(),
  'code': instance.code,
};
