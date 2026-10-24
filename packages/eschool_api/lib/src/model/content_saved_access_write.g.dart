// GENERATED CODE - DO NOT MODIFY BY HAND

part of 'content_saved_access_write.dart';

// **************************************************************************
// JsonSerializableGenerator
// **************************************************************************

ContentSavedAccessWrite _$ContentSavedAccessWriteFromJson(
  Map<String, dynamic> json,
) => $checkedCreate('ContentSavedAccessWrite', json, ($checkedConvert) {
  $checkKeys(json, requiredKeys: const ['version_ids']);
  final val = ContentSavedAccessWrite(
    versionIds: $checkedConvert(
      'version_ids',
      (v) => (v as List<dynamic>).map((e) => (e as num).toInt()).toSet(),
    ),
  );
  return val;
}, fieldKeyMap: const {'versionIds': 'version_ids'});

Map<String, dynamic> _$ContentSavedAccessWriteToJson(
  ContentSavedAccessWrite instance,
) => <String, dynamic>{'version_ids': instance.versionIds.toList()};
