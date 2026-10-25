// GENERATED CODE - DO NOT MODIFY BY HAND

part of 'progress_band_settings_response.dart';

// **************************************************************************
// JsonSerializableGenerator
// **************************************************************************

ProgressBandSettingsResponse _$ProgressBandSettingsResponseFromJson(
  Map<String, dynamic> json,
) => $checkedCreate('ProgressBandSettingsResponse', json, ($checkedConvert) {
  $checkKeys(json, requiredKeys: const ['error', 'message', 'code', 'data']);
  final val = ProgressBandSettingsResponse(
    error: $checkedConvert('error', (v) => v as bool),
    message: $checkedConvert('message', (v) => v as String),
    code: $checkedConvert('code', (v) => (v as num).toInt()),
    data: $checkedConvert(
      'data',
      (v) => ProgressBandSettings.fromJson(v as Map<String, dynamic>),
    ),
  );
  return val;
});

Map<String, dynamic> _$ProgressBandSettingsResponseToJson(
  ProgressBandSettingsResponse instance,
) => <String, dynamic>{
  'error': instance.error,
  'message': instance.message,
  'code': instance.code,
  'data': instance.data.toJson(),
};
