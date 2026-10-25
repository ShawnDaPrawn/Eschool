// GENERATED CODE - DO NOT MODIFY BY HAND

part of 'gallery_group.dart';

// **************************************************************************
// JsonSerializableGenerator
// **************************************************************************

GalleryGroup _$GalleryGroupFromJson(Map<String, dynamic> json) =>
    $checkedCreate(
      'GalleryGroup',
      json,
      ($checkedConvert) {
        $checkKeys(
          json,
          requiredKeys: const ['id', 'name', 'session_year_id', 'can_upload'],
        );
        final val = GalleryGroup(
          id: $checkedConvert('id', (v) => (v as num).toInt()),
          name: $checkedConvert('name', (v) => v as String),
          sessionYearId: $checkedConvert(
            'session_year_id',
            (v) => (v as num).toInt(),
          ),
          canUpload: $checkedConvert('can_upload', (v) => v as bool),
        );
        return val;
      },
      fieldKeyMap: const {
        'sessionYearId': 'session_year_id',
        'canUpload': 'can_upload',
      },
    );

Map<String, dynamic> _$GalleryGroupToJson(GalleryGroup instance) =>
    <String, dynamic>{
      'id': instance.id,
      'name': instance.name,
      'session_year_id': instance.sessionYearId,
      'can_upload': instance.canUpload,
    };
