// GENERATED CODE - DO NOT MODIFY BY HAND

part of 'content_library_item.dart';

// **************************************************************************
// JsonSerializableGenerator
// **************************************************************************

ContentLibraryItem _$ContentLibraryItemFromJson(Map<String, dynamic> json) =>
    $checkedCreate(
      'ContentLibraryItem',
      json,
      ($checkedConvert) {
        $checkKeys(
          json,
          requiredKeys: const [
            'id',
            'version_id',
            'title',
            'summary',
            'subject_id',
            'subject_name',
            'published_at',
          ],
        );
        final val = ContentLibraryItem(
          id: $checkedConvert('id', (v) => (v as num).toInt()),
          versionId: $checkedConvert('version_id', (v) => (v as num).toInt()),
          title: $checkedConvert('title', (v) => v as String),
          summary: $checkedConvert('summary', (v) => v as String),
          subjectId: $checkedConvert('subject_id', (v) => (v as num).toInt()),
          subjectName: $checkedConvert('subject_name', (v) => v as String),
          publishedAt: $checkedConvert(
            'published_at',
            (v) => DateTime.parse(v as String),
          ),
        );
        return val;
      },
      fieldKeyMap: const {
        'versionId': 'version_id',
        'subjectId': 'subject_id',
        'subjectName': 'subject_name',
        'publishedAt': 'published_at',
      },
    );

Map<String, dynamic> _$ContentLibraryItemToJson(ContentLibraryItem instance) =>
    <String, dynamic>{
      'id': instance.id,
      'version_id': instance.versionId,
      'title': instance.title,
      'summary': instance.summary,
      'subject_id': instance.subjectId,
      'subject_name': instance.subjectName,
      'published_at': instance.publishedAt.toIso8601String(),
    };
