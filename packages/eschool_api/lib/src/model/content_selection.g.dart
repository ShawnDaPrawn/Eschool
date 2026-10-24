// GENERATED CODE - DO NOT MODIFY BY HAND

part of 'content_selection.dart';

// **************************************************************************
// JsonSerializableGenerator
// **************************************************************************

ContentSelection _$ContentSelectionFromJson(Map<String, dynamic> json) =>
    $checkedCreate(
      'ContentSelection',
      json,
      ($checkedConvert) {
        $checkKeys(
          json,
          requiredKeys: const [
            'subject_id',
            'grade_level_id',
            'session_year_id',
            'title',
            'summary',
            'group_ids',
            'topic_ids',
          ],
        );
        final val = ContentSelection(
          subjectId: $checkedConvert('subject_id', (v) => (v as num).toInt()),
          gradeLevelId: $checkedConvert(
            'grade_level_id',
            (v) => (v as num).toInt(),
          ),
          sessionYearId: $checkedConvert(
            'session_year_id',
            (v) => (v as num).toInt(),
          ),
          title: $checkedConvert('title', (v) => v as String),
          summary: $checkedConvert('summary', (v) => v as String?),
          groupIds: $checkedConvert(
            'group_ids',
            (v) => (v as List<dynamic>).map((e) => (e as num).toInt()).toSet(),
          ),
          topicIds: $checkedConvert(
            'topic_ids',
            (v) => (v as List<dynamic>).map((e) => (e as num).toInt()).toSet(),
          ),
        );
        return val;
      },
      fieldKeyMap: const {
        'subjectId': 'subject_id',
        'gradeLevelId': 'grade_level_id',
        'sessionYearId': 'session_year_id',
        'groupIds': 'group_ids',
        'topicIds': 'topic_ids',
      },
    );

Map<String, dynamic> _$ContentSelectionToJson(ContentSelection instance) =>
    <String, dynamic>{
      'subject_id': instance.subjectId,
      'grade_level_id': instance.gradeLevelId,
      'session_year_id': instance.sessionYearId,
      'title': instance.title,
      'summary': instance.summary,
      'group_ids': instance.groupIds.toList(),
      'topic_ids': instance.topicIds.toList(),
    };
