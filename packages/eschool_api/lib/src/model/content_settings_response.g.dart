// GENERATED CODE - DO NOT MODIFY BY HAND

part of 'content_settings_response.dart';

// **************************************************************************
// JsonSerializableGenerator
// **************************************************************************

ContentSettingsResponse _$ContentSettingsResponseFromJson(
  Map<String, dynamic> json,
) => $checkedCreate('ContentSettingsResponse', json, ($checkedConvert) {
  $checkKeys(json, requiredKeys: const ['error', 'message', 'code', 'data']);
  final val = ContentSettingsResponse(
    error: $checkedConvert('error', (v) => v as bool),
    message: $checkedConvert('message', (v) => v as String),
    code: $checkedConvert('code', (v) => (v as num).toInt()),
    data: $checkedConvert(
      'data',
      (v) => ContentSettings.fromJson(v as Map<String, dynamic>),
    ),
  );
  return val;
});

Map<String, dynamic> _$ContentSettingsResponseToJson(
  ContentSettingsResponse instance,
) => <String, dynamic>{
  'error': instance.error,
  'message': instance.message,
  'code': instance.code,
  'data': instance.data.toJson(),
};
