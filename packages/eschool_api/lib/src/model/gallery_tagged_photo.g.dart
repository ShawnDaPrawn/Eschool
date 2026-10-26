// GENERATED CODE - DO NOT MODIFY BY HAND

part of 'gallery_tagged_photo.dart';

// **************************************************************************
// JsonSerializableGenerator
// **************************************************************************

GalleryTaggedPhoto _$GalleryTaggedPhotoFromJson(Map<String, dynamic> json) =>
    $checkedCreate(
      'GalleryTaggedPhoto',
      json,
      ($checkedConvert) {
        $checkKeys(
          json,
          requiredKeys: const [
            'tag_id',
            'tag_revision',
            'status',
            'retained_on_transfer',
            'photo',
          ],
        );
        final val = GalleryTaggedPhoto(
          tagId: $checkedConvert('tag_id', (v) => (v as num).toInt()),
          tagRevision: $checkedConvert(
            'tag_revision',
            (v) => (v as num).toInt(),
          ),
          status: $checkedConvert(
            'status',
            (v) => $enumDecode(
              _$GalleryTaggedPhotoStatusEnumEnumMap,
              v,
              unknownValue: GalleryTaggedPhotoStatusEnum.unknownDefaultOpenApi,
            ),
          ),
          retainedOnTransfer: $checkedConvert(
            'retained_on_transfer',
            (v) => v as bool,
          ),
          photo: $checkedConvert(
            'photo',
            (v) => GalleryPhoto.fromJson(v as Map<String, dynamic>),
          ),
        );
        return val;
      },
      fieldKeyMap: const {
        'tagId': 'tag_id',
        'tagRevision': 'tag_revision',
        'retainedOnTransfer': 'retained_on_transfer',
      },
    );

Map<String, dynamic> _$GalleryTaggedPhotoToJson(GalleryTaggedPhoto instance) =>
    <String, dynamic>{
      'tag_id': instance.tagId,
      'tag_revision': instance.tagRevision,
      'status': _$GalleryTaggedPhotoStatusEnumEnumMap[instance.status]!,
      'retained_on_transfer': instance.retainedOnTransfer,
      'photo': instance.photo.toJson(),
    };

const _$GalleryTaggedPhotoStatusEnumEnumMap = {
  GalleryTaggedPhotoStatusEnum.awaitingGuardian: 'awaiting_guardian',
  GalleryTaggedPhotoStatusEnum.approved: 'approved',
  GalleryTaggedPhotoStatusEnum.unknownDefaultOpenApi:
      'unknown_default_open_api',
};
