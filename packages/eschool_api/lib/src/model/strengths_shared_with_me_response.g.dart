// GENERATED CODE - DO NOT MODIFY BY HAND

part of 'strengths_shared_with_me_response.dart';

// **************************************************************************
// JsonSerializableGenerator
// **************************************************************************

StrengthsSharedWithMeResponse _$StrengthsSharedWithMeResponseFromJson(
  Map<String, dynamic> json,
) => $checkedCreate('StrengthsSharedWithMeResponse', json, ($checkedConvert) {
  $checkKeys(json, requiredKeys: const ['error', 'message', 'data', 'code']);
  final val = StrengthsSharedWithMeResponse(
    error: $checkedConvert('error', (v) => v as bool),
    message: $checkedConvert('message', (v) => v as String),
    data: $checkedConvert(
      'data',
      (v) =>
          StrengthsSharedWithMeResponseData.fromJson(v as Map<String, dynamic>),
    ),
    code: $checkedConvert('code', (v) => (v as num).toInt()),
  );
  return val;
});

Map<String, dynamic> _$StrengthsSharedWithMeResponseToJson(
  StrengthsSharedWithMeResponse instance,
) => <String, dynamic>{
  'error': instance.error,
  'message': instance.message,
  'data': instance.data.toJson(),
  'code': instance.code,
};
