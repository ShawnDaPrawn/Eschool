// GENERATED CODE - DO NOT MODIFY BY HAND

part of 'gallery_propose.dart';

// **************************************************************************
// JsonSerializableGenerator
// **************************************************************************

GalleryPropose _$GalleryProposeFromJson(Map<String, dynamic> json) =>
    $checkedCreate(
      'GalleryPropose',
      json,
      ($checkedConvert) {
        $checkKeys(json, requiredKeys: const ['operation_id']);
        final val = GalleryPropose(
          operationId: $checkedConvert('operation_id', (v) => v as String),
          learnerId: $checkedConvert('learner_id', (v) => (v as num?)?.toInt()),
          handle: $checkedConvert('handle', (v) => v as String?),
        );
        return val;
      },
      fieldKeyMap: const {
        'operationId': 'operation_id',
        'learnerId': 'learner_id',
      },
    );

Map<String, dynamic> _$GalleryProposeToJson(GalleryPropose instance) =>
    <String, dynamic>{
      'operation_id': instance.operationId,
      'learner_id': ?instance.learnerId,
      'handle': ?instance.handle,
    };
