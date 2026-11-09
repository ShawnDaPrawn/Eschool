// GENERATED CODE - DO NOT MODIFY BY HAND

part of 'practice_quiz_topics.dart';

// **************************************************************************
// JsonSerializableGenerator
// **************************************************************************

PracticeQuizTopics _$PracticeQuizTopicsFromJson(
  Map<String, dynamic> json,
) => $checkedCreate(
  'PracticeQuizTopics',
  json,
  ($checkedConvert) {
    $checkKeys(
      json,
      requiredKeys: const [
        'learner_id',
        'group_id',
        'topics',
        'topics_max',
        'questions_min',
        'questions_max',
        'writable',
      ],
    );
    final val = PracticeQuizTopics(
      learnerId: $checkedConvert('learner_id', (v) => (v as num).toInt()),
      groupId: $checkedConvert('group_id', (v) => (v as num).toInt()),
      topics: $checkedConvert(
        'topics',
        (v) => (v as List<dynamic>)
            .map(
              (e) =>
                  PracticeTestTopicOption.fromJson(e as Map<String, dynamic>),
            )
            .toList(),
      ),
      topicsMax: $checkedConvert('topics_max', (v) => (v as num).toInt()),
      questionsMin: $checkedConvert('questions_min', (v) => (v as num).toInt()),
      questionsMax: $checkedConvert('questions_max', (v) => (v as num).toInt()),
      writable: $checkedConvert('writable', (v) => v as bool),
    );
    return val;
  },
  fieldKeyMap: const {
    'learnerId': 'learner_id',
    'groupId': 'group_id',
    'topicsMax': 'topics_max',
    'questionsMin': 'questions_min',
    'questionsMax': 'questions_max',
  },
);

Map<String, dynamic> _$PracticeQuizTopicsToJson(PracticeQuizTopics instance) =>
    <String, dynamic>{
      'learner_id': instance.learnerId,
      'group_id': instance.groupId,
      'topics': instance.topics.map((e) => e.toJson()).toList(),
      'topics_max': instance.topicsMax,
      'questions_min': instance.questionsMin,
      'questions_max': instance.questionsMax,
      'writable': instance.writable,
    };
