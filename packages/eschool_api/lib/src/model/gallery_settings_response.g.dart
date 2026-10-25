// GENERATED CODE - DO NOT MODIFY BY HAND

part of 'gallery_settings_response.dart';

// **************************************************************************
// JsonSerializableGenerator
// **************************************************************************

GallerySettingsResponse _$GallerySettingsResponseFromJson(
  Map<String, dynamic> json,
) => $checkedCreate('GallerySettingsResponse', json, ($checkedConvert) {
  $checkKeys(json, requiredKeys: const ['error', 'message', 'data', 'code']);
  final val = GallerySettingsResponse(
    error: $checkedConvert('error', (v) => v as bool),
    message: $checkedConvert('message', (v) => v as String),
    data: $checkedConvert(
      'data',
      (v) => GallerySettings.fromJson(v as Map<String, dynamic>),
    ),
    code: $checkedConvert('code', (v) => (v as num).toInt()),
  );
  return val;
});

Map<String, dynamic> _$GallerySettingsResponseToJson(
  GallerySettingsResponse instance,
) => <String, dynamic>{
  'error': instance.error,
  'message': instance.message,
  'data': instance.data.toJson(),
  'code': instance.code,
};
