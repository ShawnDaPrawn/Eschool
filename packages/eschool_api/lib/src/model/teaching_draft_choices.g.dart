// GENERATED CODE - DO NOT MODIFY BY HAND

part of 'teaching_draft_choices.dart';

// **************************************************************************
// JsonSerializableGenerator
// **************************************************************************

TeachingDraftChoices _$TeachingDraftChoicesFromJson(
  Map<String, dynamic> json,
) => $checkedCreate(
  'TeachingDraftChoices',
  json,
  ($checkedConvert) {
    $checkKeys(
      json,
      requiredKeys: const [
        'school_id',
        'reader_id',
        'group_id',
        'sources',
        'topics',
        'tasks',
      ],
    );
    final val = TeachingDraftChoices(
      schoolId: $checkedConvert('school_id', (v) => (v as num).toInt()),
      readerId: $checkedConvert('reader_id', (v) => (v as num).toInt()),
      groupId: $checkedConvert('group_id', (v) => (v as num).toInt()),
      sources: $checkedConvert(
        'sources',
        (v) => (v as List<dynamic>)
            .map(
              (e) => TeachingEvidenceChoice.fromJson(e as Map<String, dynamic>),
            )
            .toList(),
      ),
      topics: $checkedConvert(
        'topics',
        (v) => (v as List<dynamic>)
            .map(
              (e) => TeachingEvidenceChoice.fromJson(e as Map<String, dynamic>),
            )
            .toList(),
      ),
      tasks: $checkedConvert(
        'tasks',
        (v) => (v as List<dynamic>)
            .map((e) => TeachingTaskChoice.fromJson(e as Map<String, dynamic>))
            .toList(),
      ),
    );
    return val;
  },
  fieldKeyMap: const {
    'schoolId': 'school_id',
    'readerId': 'reader_id',
    'groupId': 'group_id',
  },
);

Map<String, dynamic> _$TeachingDraftChoicesToJson(
  TeachingDraftChoices instance,
) => <String, dynamic>{
  'school_id': instance.schoolId,
  'reader_id': instance.readerId,
  'group_id': instance.groupId,
  'sources': instance.sources.map((e) => e.toJson()).toList(),
  'topics': instance.topics.map((e) => e.toJson()).toList(),
  'tasks': instance.tasks.map((e) => e.toJson()).toList(),
};
