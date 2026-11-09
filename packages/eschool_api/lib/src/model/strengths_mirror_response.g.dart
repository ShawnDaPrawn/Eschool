// GENERATED CODE - DO NOT MODIFY BY HAND

part of 'strengths_mirror_response.dart';

// **************************************************************************
// JsonSerializableGenerator
// **************************************************************************

StrengthsMirrorResponse _$StrengthsMirrorResponseFromJson(
  Map<String, dynamic> json,
) => $checkedCreate('StrengthsMirrorResponse', json, ($checkedConvert) {
  $checkKeys(json, requiredKeys: const ['error', 'message', 'data', 'code']);
  final val = StrengthsMirrorResponse(
    error: $checkedConvert('error', (v) => v as bool),
    message: $checkedConvert('message', (v) => v as String),
    data: $checkedConvert(
      'data',
      (v) => StrengthsMirror.fromJson(v as Map<String, dynamic>),
    ),
    code: $checkedConvert('code', (v) => (v as num).toInt()),
  );
  return val;
});

Map<String, dynamic> _$StrengthsMirrorResponseToJson(
  StrengthsMirrorResponse instance,
) => <String, dynamic>{
  'error': instance.error,
  'message': instance.message,
  'data': instance.data.toJson(),
  'code': instance.code,
};
