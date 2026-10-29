// GENERATED CODE - DO NOT MODIFY BY HAND

part of 'commerce_order_page_response.dart';

// **************************************************************************
// JsonSerializableGenerator
// **************************************************************************

CommerceOrderPageResponse _$CommerceOrderPageResponseFromJson(
  Map<String, dynamic> json,
) => $checkedCreate('CommerceOrderPageResponse', json, ($checkedConvert) {
  $checkKeys(json, requiredKeys: const ['error', 'message', 'data', 'code']);
  final val = CommerceOrderPageResponse(
    error: $checkedConvert('error', (v) => v as bool),
    message: $checkedConvert('message', (v) => v as String),
    data: $checkedConvert(
      'data',
      (v) => CommerceOrderPage.fromJson(v as Map<String, dynamic>),
    ),
    code: $checkedConvert('code', (v) => (v as num).toInt()),
  );
  return val;
});

Map<String, dynamic> _$CommerceOrderPageResponseToJson(
  CommerceOrderPageResponse instance,
) => <String, dynamic>{
  'error': instance.error,
  'message': instance.message,
  'data': instance.data.toJson(),
  'code': instance.code,
};
