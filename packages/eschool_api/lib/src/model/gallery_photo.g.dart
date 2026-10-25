// GENERATED CODE - DO NOT MODIFY BY HAND

part of 'gallery_photo.dart';

// **************************************************************************
// JsonSerializableGenerator
// **************************************************************************

GalleryPhoto _$GalleryPhotoFromJson(Map<String, dynamic> json) =>
    $checkedCreate(
      'GalleryPhoto',
      json,
      ($checkedConvert) {
        $checkKeys(
          json,
          requiredKeys: const [
            'id',
            'public_id',
            'caption',
            'captured_on',
            'school_year',
            'session_year_id',
            'activity_group_id',
            'scope',
            'width',
            'height',
            'active',
            'revision',
          ],
        );
        final val = GalleryPhoto(
          id: $checkedConvert('id', (v) => (v as num).toInt()),
          publicId: $checkedConvert('public_id', (v) => v as String),
          caption: $checkedConvert('caption', (v) => v as String?),
          capturedOn: $checkedConvert('captured_on', (v) => v as String),
          schoolYear: $checkedConvert('school_year', (v) => v as String),
          sessionYearId: $checkedConvert(
            'session_year_id',
            (v) => (v as num).toInt(),
          ),
          activityGroupId: $checkedConvert(
            'activity_group_id',
            (v) => (v as num?)?.toInt(),
          ),
          scope: $checkedConvert(
            'scope',
            (v) => $enumDecode(
              _$GalleryPhotoScopeEnumEnumMap,
              v,
              unknownValue: GalleryPhotoScopeEnum.unknownDefaultOpenApi,
            ),
          ),
          width: $checkedConvert('width', (v) => (v as num).toInt()),
          height: $checkedConvert('height', (v) => (v as num).toInt()),
          active: $checkedConvert('active', (v) => v as bool),
          revision: $checkedConvert('revision', (v) => (v as num).toInt()),
        );
        return val;
      },
      fieldKeyMap: const {
        'publicId': 'public_id',
        'capturedOn': 'captured_on',
        'schoolYear': 'school_year',
        'sessionYearId': 'session_year_id',
        'activityGroupId': 'activity_group_id',
      },
    );

Map<String, dynamic> _$GalleryPhotoToJson(GalleryPhoto instance) =>
    <String, dynamic>{
      'id': instance.id,
      'public_id': instance.publicId,
      'caption': instance.caption,
      'captured_on': instance.capturedOn,
      'school_year': instance.schoolYear,
      'session_year_id': instance.sessionYearId,
      'activity_group_id': instance.activityGroupId,
      'scope': _$GalleryPhotoScopeEnumEnumMap[instance.scope]!,
      'width': instance.width,
      'height': instance.height,
      'active': instance.active,
      'revision': instance.revision,
    };

const _$GalleryPhotoScopeEnumEnumMap = {
  GalleryPhotoScopeEnum.family: 'family',
  GalleryPhotoScopeEnum.group: 'group',
  GalleryPhotoScopeEnum.unknownDefaultOpenApi: 'unknown_default_open_api',
};
