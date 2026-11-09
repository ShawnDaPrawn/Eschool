// GENERATED CODE - DO NOT MODIFY BY HAND

part of 'strengths_share_response.dart';

// **************************************************************************
// JsonSerializableGenerator
// **************************************************************************

StrengthsShareResponse _$StrengthsShareResponseFromJson(
  Map<String, dynamic> json,
) => $checkedCreate('StrengthsShareResponse', json, ($checkedConvert) {
  $checkKeys(json, requiredKeys: const ['error', 'message', 'data', 'code']);
  final val = StrengthsShareResponse(
    error: $checkedConvert('error', (v) => v as bool),
    message: $checkedConvert('message', (v) => v as String),
    data: $checkedConvert(
      'data',
      (v) => StrengthsShare.fromJson(v as Map<String, dynamic>),
    ),
    code: $checkedConvert('code', (v) => (v as num).toInt()),
  );
  return val;
});

Map<String, dynamic> _$StrengthsShareResponseToJson(
  StrengthsShareResponse instance,
) => <String, dynamic>{
  'error': instance.error,
  'message': instance.message,
  'data': instance.data.toJson(),
  'code': instance.code,
};
