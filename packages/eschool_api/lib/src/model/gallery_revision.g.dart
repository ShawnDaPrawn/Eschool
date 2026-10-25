// GENERATED CODE - DO NOT MODIFY BY HAND

part of 'gallery_revision.dart';

// **************************************************************************
// JsonSerializableGenerator
// **************************************************************************

GalleryRevision _$GalleryRevisionFromJson(Map<String, dynamic> json) =>
    $checkedCreate('GalleryRevision', json, ($checkedConvert) {
      $checkKeys(json, requiredKeys: const ['operation_id', 'revision']);
      final val = GalleryRevision(
        operationId: $checkedConvert('operation_id', (v) => v as String),
        revision: $checkedConvert('revision', (v) => (v as num).toInt()),
      );
      return val;
    }, fieldKeyMap: const {'operationId': 'operation_id'});

Map<String, dynamic> _$GalleryRevisionToJson(GalleryRevision instance) =>
    <String, dynamic>{
      'operation_id': instance.operationId,
      'revision': instance.revision,
    };
