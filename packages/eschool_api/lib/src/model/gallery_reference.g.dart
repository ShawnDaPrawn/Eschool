// GENERATED CODE - DO NOT MODIFY BY HAND

part of 'gallery_reference.dart';

// **************************************************************************
// JsonSerializableGenerator
// **************************************************************************

GalleryReference _$GalleryReferenceFromJson(Map<String, dynamic> json) =>
    $checkedCreate('GalleryReference', json, ($checkedConvert) {
      $checkKeys(json, requiredKeys: const ['id', 'revision']);
      final val = GalleryReference(
        id: $checkedConvert('id', (v) => (v as num).toInt()),
        revision: $checkedConvert('revision', (v) => (v as num).toInt()),
      );
      return val;
    });

Map<String, dynamic> _$GalleryReferenceToJson(GalleryReference instance) =>
    <String, dynamic>{'id': instance.id, 'revision': instance.revision};
