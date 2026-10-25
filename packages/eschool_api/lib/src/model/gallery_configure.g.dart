// GENERATED CODE - DO NOT MODIFY BY HAND

part of 'gallery_configure.dart';

// **************************************************************************
// JsonSerializableGenerator
// **************************************************************************

GalleryConfigure _$GalleryConfigureFromJson(Map<String, dynamic> json) =>
    $checkedCreate(
      'GalleryConfigure',
      json,
      ($checkedConvert) {
        $checkKeys(
          json,
          requiredKeys: const ['operation_id', 'revision', 'guardian_required'],
        );
        final val = GalleryConfigure(
          operationId: $checkedConvert('operation_id', (v) => v as String),
          revision: $checkedConvert('revision', (v) => (v as num).toInt()),
          guardianRequired: $checkedConvert(
            'guardian_required',
            (v) => v as bool,
          ),
        );
        return val;
      },
      fieldKeyMap: const {
        'operationId': 'operation_id',
        'guardianRequired': 'guardian_required',
      },
    );

Map<String, dynamic> _$GalleryConfigureToJson(GalleryConfigure instance) =>
    <String, dynamic>{
      'operation_id': instance.operationId,
      'revision': instance.revision,
      'guardian_required': instance.guardianRequired,
    };
