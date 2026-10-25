// GENERATED CODE - DO NOT MODIFY BY HAND

part of 'gallery_group_page_response.dart';

// **************************************************************************
// JsonSerializableGenerator
// **************************************************************************

GalleryGroupPageResponse _$GalleryGroupPageResponseFromJson(
  Map<String, dynamic> json,
) => $checkedCreate('GalleryGroupPageResponse', json, ($checkedConvert) {
  $checkKeys(json, requiredKeys: const ['error', 'message', 'data', 'code']);
  final val = GalleryGroupPageResponse(
    error: $checkedConvert('error', (v) => v as bool),
    message: $checkedConvert('message', (v) => v as String),
    data: $checkedConvert(
      'data',
      (v) => GalleryGroupPage.fromJson(v as Map<String, dynamic>),
    ),
    code: $checkedConvert('code', (v) => (v as num).toInt()),
  );
  return val;
});

Map<String, dynamic> _$GalleryGroupPageResponseToJson(
  GalleryGroupPageResponse instance,
) => <String, dynamic>{
  'error': instance.error,
  'message': instance.message,
  'data': instance.data.toJson(),
  'code': instance.code,
};
