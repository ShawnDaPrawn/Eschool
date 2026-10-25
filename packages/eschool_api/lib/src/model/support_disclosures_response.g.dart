// GENERATED CODE - DO NOT MODIFY BY HAND

part of 'support_disclosures_response.dart';

// **************************************************************************
// JsonSerializableGenerator
// **************************************************************************

SupportDisclosuresResponse _$SupportDisclosuresResponseFromJson(
  Map<String, dynamic> json,
) => $checkedCreate('SupportDisclosuresResponse', json, ($checkedConvert) {
  $checkKeys(json, requiredKeys: const ['error', 'message', 'code', 'data']);
  final val = SupportDisclosuresResponse(
    error: $checkedConvert('error', (v) => v as bool),
    message: $checkedConvert('message', (v) => v as String),
    code: $checkedConvert('code', (v) => (v as num).toInt()),
    data: $checkedConvert(
      'data',
      (v) => SupportDisclosures.fromJson(v as Map<String, dynamic>),
    ),
  );
  return val;
});

Map<String, dynamic> _$SupportDisclosuresResponseToJson(
  SupportDisclosuresResponse instance,
) => <String, dynamic>{
  'error': instance.error,
  'message': instance.message,
  'code': instance.code,
  'data': instance.data.toJson(),
};
