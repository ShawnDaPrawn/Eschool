// GENERATED CODE - DO NOT MODIFY BY HAND

part of 'shared_library_copy.dart';

// **************************************************************************
// JsonSerializableGenerator
// **************************************************************************

SharedLibraryCopy _$SharedLibraryCopyFromJson(Map<String, dynamic> json) =>
    $checkedCreate(
      'SharedLibraryCopy',
      json,
      ($checkedConvert) {
        $checkKeys(
          json,
          requiredKeys: const [
            'id',
            'version_id',
            'revision',
            'active',
            'library_item_id',
          ],
        );
        final val = SharedLibraryCopy(
          id: $checkedConvert('id', (v) => (v as num).toInt()),
          versionId: $checkedConvert('version_id', (v) => (v as num).toInt()),
          revision: $checkedConvert('revision', (v) => (v as num).toInt()),
          active: $checkedConvert('active', (v) => v as bool),
          libraryItemId: $checkedConvert(
            'library_item_id',
            (v) => (v as num).toInt(),
          ),
        );
        return val;
      },
      fieldKeyMap: const {
        'versionId': 'version_id',
        'libraryItemId': 'library_item_id',
      },
    );

Map<String, dynamic> _$SharedLibraryCopyToJson(SharedLibraryCopy instance) =>
    <String, dynamic>{
      'id': instance.id,
      'version_id': instance.versionId,
      'revision': instance.revision,
      'active': instance.active,
      'library_item_id': instance.libraryItemId,
    };
