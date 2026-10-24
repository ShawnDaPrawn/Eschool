// GENERATED CODE - DO NOT MODIFY BY HAND

part of 'content_topic_choice.dart';

// **************************************************************************
// JsonSerializableGenerator
// **************************************************************************

ContentTopicChoice _$ContentTopicChoiceFromJson(Map<String, dynamic> json) =>
    $checkedCreate(
      'ContentTopicChoice',
      json,
      ($checkedConvert) {
        $checkKeys(
          json,
          requiredKeys: const [
            'id',
            'name',
            'subject_id',
            'grade_level_id',
            'curriculum_version',
          ],
        );
        final val = ContentTopicChoice(
          id: $checkedConvert('id', (v) => (v as num).toInt()),
          name: $checkedConvert('name', (v) => v as String),
          subjectId: $checkedConvert('subject_id', (v) => (v as num).toInt()),
          gradeLevelId: $checkedConvert(
            'grade_level_id',
            (v) => (v as num).toInt(),
          ),
          curriculumVersion: $checkedConvert(
            'curriculum_version',
            (v) => (v as num).toInt(),
          ),
        );
        return val;
      },
      fieldKeyMap: const {
        'subjectId': 'subject_id',
        'gradeLevelId': 'grade_level_id',
        'curriculumVersion': 'curriculum_version',
      },
    );

Map<String, dynamic> _$ContentTopicChoiceToJson(ContentTopicChoice instance) =>
    <String, dynamic>{
      'id': instance.id,
      'name': instance.name,
      'subject_id': instance.subjectId,
      'grade_level_id': instance.gradeLevelId,
      'curriculum_version': instance.curriculumVersion,
    };
