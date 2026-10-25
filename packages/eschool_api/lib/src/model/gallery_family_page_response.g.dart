// GENERATED CODE - DO NOT MODIFY BY HAND

part of 'gallery_family_page_response.dart';

// **************************************************************************
// JsonSerializableGenerator
// **************************************************************************

GalleryFamilyPageResponse _$GalleryFamilyPageResponseFromJson(
  Map<String, dynamic> json,
) => $checkedCreate('GalleryFamilyPageResponse', json, ($checkedConvert) {
  $checkKeys(json, requiredKeys: const ['error', 'message', 'data', 'code']);
  final val = GalleryFamilyPageResponse(
    error: $checkedConvert('error', (v) => v as bool),
    message: $checkedConvert('message', (v) => v as String),
    data: $checkedConvert(
      'data',
      (v) => GalleryFamilyPage.fromJson(v as Map<String, dynamic>),
    ),
    code: $checkedConvert('code', (v) => (v as num).toInt()),
  );
  return val;
});

Map<String, dynamic> _$GalleryFamilyPageResponseToJson(
  GalleryFamilyPageResponse instance,
) => <String, dynamic>{
  'error': instance.error,
  'message': instance.message,
  'data': instance.data.toJson(),
  'code': instance.code,
};
