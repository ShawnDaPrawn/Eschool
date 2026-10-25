// GENERATED CODE - DO NOT MODIFY BY HAND

part of 'progress_family_bands_response.dart';

// **************************************************************************
// JsonSerializableGenerator
// **************************************************************************

ProgressFamilyBandsResponse _$ProgressFamilyBandsResponseFromJson(
  Map<String, dynamic> json,
) => $checkedCreate('ProgressFamilyBandsResponse', json, ($checkedConvert) {
  $checkKeys(json, requiredKeys: const ['error', 'message', 'code', 'data']);
  final val = ProgressFamilyBandsResponse(
    error: $checkedConvert('error', (v) => v as bool),
    message: $checkedConvert('message', (v) => v as String),
    code: $checkedConvert('code', (v) => (v as num).toInt()),
    data: $checkedConvert(
      'data',
      (v) => ProgressFamilyBands.fromJson(v as Map<String, dynamic>),
    ),
  );
  return val;
});

Map<String, dynamic> _$ProgressFamilyBandsResponseToJson(
  ProgressFamilyBandsResponse instance,
) => <String, dynamic>{
  'error': instance.error,
  'message': instance.message,
  'code': instance.code,
  'data': instance.data.toJson(),
};
