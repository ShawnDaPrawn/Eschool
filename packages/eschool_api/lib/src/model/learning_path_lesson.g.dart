// GENERATED CODE - DO NOT MODIFY BY HAND

part of 'learning_path_lesson.dart';

// **************************************************************************
// JsonSerializableGenerator
// **************************************************************************

LearningPathLesson _$LearningPathLessonFromJson(Map<String, dynamic> json) =>
    $checkedCreate(
      'LearningPathLesson',
      json,
      ($checkedConvert) {
        $checkKeys(
          json,
          requiredKeys: const [
            'source',
            'content_version_id',
            'library_item_version_id',
            'title',
            'body',
          ],
        );
        final val = LearningPathLesson(
          source_: $checkedConvert(
            'source',
            (v) => $enumDecode(
              _$LearningPathLessonSource_EnumEnumMap,
              v,
              unknownValue: LearningPathLessonSource_Enum.unknownDefaultOpenApi,
            ),
          ),
          contentVersionId: $checkedConvert(
            'content_version_id',
            (v) => (v as num?)?.toInt(),
          ),
          libraryItemVersionId: $checkedConvert(
            'library_item_version_id',
            (v) => (v as num?)?.toInt(),
          ),
          title: $checkedConvert('title', (v) => v as String),
          body: $checkedConvert('body', (v) => v as String?),
        );
        return val;
      },
      fieldKeyMap: const {
        'source_': 'source',
        'contentVersionId': 'content_version_id',
        'libraryItemVersionId': 'library_item_version_id',
      },
    );

Map<String, dynamic> _$LearningPathLessonToJson(LearningPathLesson instance) =>
    <String, dynamic>{
      'source': _$LearningPathLessonSource_EnumEnumMap[instance.source_]!,
      'content_version_id': instance.contentVersionId,
      'library_item_version_id': instance.libraryItemVersionId,
      'title': instance.title,
      'body': instance.body,
    };

const _$LearningPathLessonSource_EnumEnumMap = {
  LearningPathLessonSource_Enum.school: 'school',
  LearningPathLessonSource_Enum.library_: 'library',
  LearningPathLessonSource_Enum.unknownDefaultOpenApi:
      'unknown_default_open_api',
};
