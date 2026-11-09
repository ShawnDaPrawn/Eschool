// GENERATED CODE - DO NOT MODIFY BY HAND

part of 'tutor_memory_subject.dart';

// **************************************************************************
// JsonSerializableGenerator
// **************************************************************************

TutorMemorySubject _$TutorMemorySubjectFromJson(Map<String, dynamic> json) =>
    $checkedCreate(
      'TutorMemorySubject',
      json,
      ($checkedConvert) {
        $checkKeys(
          json,
          requiredKeys: const [
            'subject_id',
            'subject_name',
            'sessions',
            'learning_steps',
            'explained_back',
            'topics',
            'last_active_on',
          ],
        );
        final val = TutorMemorySubject(
          subjectId: $checkedConvert('subject_id', (v) => (v as num).toInt()),
          subjectName: $checkedConvert('subject_name', (v) => v as String),
          sessions: $checkedConvert('sessions', (v) => (v as num).toInt()),
          learningSteps: $checkedConvert(
            'learning_steps',
            (v) => (v as num).toInt(),
          ),
          explainedBack: $checkedConvert(
            'explained_back',
            (v) => (v as num).toInt(),
          ),
          topics: $checkedConvert(
            'topics',
            (v) => (v as List<dynamic>)
                .map(
                  (e) => TutorMemoryTopic.fromJson(e as Map<String, dynamic>),
                )
                .toList(),
          ),
          lastActiveOn: $checkedConvert(
            'last_active_on',
            (v) => DateTime.parse(v as String),
          ),
        );
        return val;
      },
      fieldKeyMap: const {
        'subjectId': 'subject_id',
        'subjectName': 'subject_name',
        'learningSteps': 'learning_steps',
        'explainedBack': 'explained_back',
        'lastActiveOn': 'last_active_on',
      },
    );

Map<String, dynamic> _$TutorMemorySubjectToJson(TutorMemorySubject instance) =>
    <String, dynamic>{
      'subject_id': instance.subjectId,
      'subject_name': instance.subjectName,
      'sessions': instance.sessions,
      'learning_steps': instance.learningSteps,
      'explained_back': instance.explainedBack,
      'topics': instance.topics.map((e) => e.toJson()).toList(),
      'last_active_on': instance.lastActiveOn.toIso8601String(),
    };
