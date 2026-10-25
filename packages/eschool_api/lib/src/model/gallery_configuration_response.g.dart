// GENERATED CODE - DO NOT MODIFY BY HAND

part of 'gallery_configuration_response.dart';

// **************************************************************************
// JsonSerializableGenerator
// **************************************************************************

GalleryConfigurationResponse _$GalleryConfigurationResponseFromJson(
  Map<String, dynamic> json,
) => $checkedCreate('GalleryConfigurationResponse', json, ($checkedConvert) {
  $checkKeys(json, requiredKeys: const ['error', 'message', 'data', 'code']);
  final val = GalleryConfigurationResponse(
    error: $checkedConvert('error', (v) => v as bool),
    message: $checkedConvert('message', (v) => v as String),
    data: $checkedConvert(
      'data',
      (v) => GalleryConfiguration.fromJson(v as Map<String, dynamic>),
    ),
    code: $checkedConvert('code', (v) => (v as num).toInt()),
  );
  return val;
});

Map<String, dynamic> _$GalleryConfigurationResponseToJson(
  GalleryConfigurationResponse instance,
) => <String, dynamic>{
  'error': instance.error,
  'message': instance.message,
  'data': instance.data.toJson(),
  'code': instance.code,
};
