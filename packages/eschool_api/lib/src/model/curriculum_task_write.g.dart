// GENERATED CODE - DO NOT MODIFY BY HAND

part of 'curriculum_task_write.dart';

// **************************************************************************
// JsonSerializableGenerator
// **************************************************************************

CurriculumTaskWrite _$CurriculumTaskWriteFromJson(
  Map<String, dynamic> json,
) => $checkedCreate(
  'CurriculumTaskWrite',
  json,
  ($checkedConvert) {
    $checkKeys(
      json,
      requiredKeys: const [
        'name',
        'type',
        'component',
        'weight_bp',
        'max_mark',
        'topic_keys',
        'questions',
      ],
    );
    final val = CurriculumTaskWrite(
      key: $checkedConvert('key', (v) => v as String?),
      name: $checkedConvert('name', (v) => v as String),
      type: $checkedConvert('type', (v) => v as String),
      component: $checkedConvert('component', (v) => v as String),
      annualComponent: $checkedConvert('annual_component', (v) => v as String?),
      weightBp: $checkedConvert('weight_bp', (v) => (v as num).toInt()),
      annualWeightBp: $checkedConvert(
        'annual_weight_bp',
        (v) => (v as num?)?.toInt(),
      ),
      maxMark: $checkedConvert('max_mark', (v) => v as String),
      dueOn: $checkedConvert('due_on', (v) => v as String?),
      topicKeys: $checkedConvert(
        'topic_keys',
        (v) => (v as List<dynamic>).map((e) => e as String).toList(),
      ),
      questions: $checkedConvert(
        'questions',
        (v) => (v as List<dynamic>)
            .map(
              (e) =>
                  CurriculumQuestionWrite.fromJson(e as Map<String, dynamic>),
            )
            .toList(),
      ),
    );
    return val;
  },
  fieldKeyMap: const {
    'annualComponent': 'annual_component',
    'weightBp': 'weight_bp',
    'annualWeightBp': 'annual_weight_bp',
    'maxMark': 'max_mark',
    'dueOn': 'due_on',
    'topicKeys': 'topic_keys',
  },
);

Map<String, dynamic> _$CurriculumTaskWriteToJson(
  CurriculumTaskWrite instance,
) => <String, dynamic>{
  'key': ?instance.key,
  'name': instance.name,
  'type': instance.type,
  'component': instance.component,
  'annual_component': ?instance.annualComponent,
  'weight_bp': instance.weightBp,
  'annual_weight_bp': ?instance.annualWeightBp,
  'max_mark': instance.maxMark,
  'due_on': ?instance.dueOn,
  'topic_keys': instance.topicKeys,
  'questions': instance.questions.map((e) => e.toJson()).toList(),
};
