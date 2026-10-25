// GENERATED CODE - DO NOT MODIFY BY HAND

part of 'mastery_view.dart';

// **************************************************************************
// JsonSerializableGenerator
// **************************************************************************

MasteryView _$MasteryViewFromJson(Map<String, dynamic> json) => $checkedCreate(
  'MasteryView',
  json,
  ($checkedConvert) {
    $checkKeys(
      json,
      requiredKeys: const [
        'learner_id',
        'subject_id',
        'audience',
        'marks_visible',
        'policy_version',
        'topics',
        'target_topic_ids',
        'priorities',
      ],
    );
    final val = MasteryView(
      learnerId: $checkedConvert('learner_id', (v) => (v as num).toInt()),
      subjectId: $checkedConvert('subject_id', (v) => (v as num).toInt()),
      audience: $checkedConvert(
        'audience',
        (v) => $enumDecode(
          _$MasteryViewAudienceEnumEnumMap,
          v,
          unknownValue: MasteryViewAudienceEnum.unknownDefaultOpenApi,
        ),
      ),
      marksVisible: $checkedConvert('marks_visible', (v) => v as bool),
      policyVersion: $checkedConvert('policy_version', (v) => v as String),
      topics: $checkedConvert(
        'topics',
        (v) => (v as List<dynamic>)
            .map((e) => MasteryTopicView.fromJson(e as Map<String, dynamic>))
            .toList(),
      ),
      targetTopicIds: $checkedConvert(
        'target_topic_ids',
        (v) => (v as List<dynamic>).map((e) => (e as num).toInt()).toList(),
      ),
      priorities: $checkedConvert(
        'priorities',
        (v) => (v as List<dynamic>)
            .map((e) => MasteryPriority.fromJson(e as Map<String, dynamic>))
            .toList(),
      ),
    );
    return val;
  },
  fieldKeyMap: const {
    'learnerId': 'learner_id',
    'subjectId': 'subject_id',
    'marksVisible': 'marks_visible',
    'policyVersion': 'policy_version',
    'targetTopicIds': 'target_topic_ids',
  },
);

Map<String, dynamic> _$MasteryViewToJson(MasteryView instance) =>
    <String, dynamic>{
      'learner_id': instance.learnerId,
      'subject_id': instance.subjectId,
      'audience': _$MasteryViewAudienceEnumEnumMap[instance.audience]!,
      'marks_visible': instance.marksVisible,
      'policy_version': instance.policyVersion,
      'topics': instance.topics.map((e) => e.toJson()).toList(),
      'target_topic_ids': instance.targetTopicIds,
      'priorities': instance.priorities.map((e) => e.toJson()).toList(),
    };

const _$MasteryViewAudienceEnumEnumMap = {
  MasteryViewAudienceEnum.staff: 'staff',
  MasteryViewAudienceEnum.family: 'family',
  MasteryViewAudienceEnum.unknownDefaultOpenApi: 'unknown_default_open_api',
};
