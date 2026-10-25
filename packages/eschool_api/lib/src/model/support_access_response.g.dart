// GENERATED CODE - DO NOT MODIFY BY HAND

part of 'support_access_response.dart';

// **************************************************************************
// JsonSerializableGenerator
// **************************************************************************

SupportAccessResponse _$SupportAccessResponseFromJson(
  Map<String, dynamic> json,
) => $checkedCreate('SupportAccessResponse', json, ($checkedConvert) {
  $checkKeys(json, requiredKeys: const ['error', 'message', 'code', 'data']);
  final val = SupportAccessResponse(
    error: $checkedConvert('error', (v) => v as bool),
    message: $checkedConvert('message', (v) => v as String),
    code: $checkedConvert('code', (v) => (v as num).toInt()),
    data: $checkedConvert(
      'data',
      (v) => SupportAccess.fromJson(v as Map<String, dynamic>),
    ),
  );
  return val;
});

Map<String, dynamic> _$SupportAccessResponseToJson(
  SupportAccessResponse instance,
) => <String, dynamic>{
  'error': instance.error,
  'message': instance.message,
  'code': instance.code,
  'data': instance.data.toJson(),
};
