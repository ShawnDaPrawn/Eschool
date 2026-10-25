// GENERATED CODE - DO NOT MODIFY BY HAND

part of 'gallery_reference_response.dart';

// **************************************************************************
// JsonSerializableGenerator
// **************************************************************************

GalleryReferenceResponse _$GalleryReferenceResponseFromJson(
  Map<String, dynamic> json,
) => $checkedCreate('GalleryReferenceResponse', json, ($checkedConvert) {
  $checkKeys(json, requiredKeys: const ['error', 'message', 'data', 'code']);
  final val = GalleryReferenceResponse(
    error: $checkedConvert('error', (v) => v as bool),
    message: $checkedConvert('message', (v) => v as String),
    data: $checkedConvert(
      'data',
      (v) => GalleryReference.fromJson(v as Map<String, dynamic>),
    ),
    code: $checkedConvert('code', (v) => (v as num).toInt()),
  );
  return val;
});

Map<String, dynamic> _$GalleryReferenceResponseToJson(
  GalleryReferenceResponse instance,
) => <String, dynamic>{
  'error': instance.error,
  'message': instance.message,
  'data': instance.data.toJson(),
  'code': instance.code,
};
