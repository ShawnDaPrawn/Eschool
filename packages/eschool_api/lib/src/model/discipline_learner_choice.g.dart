// GENERATED CODE - DO NOT MODIFY BY HAND

part of 'discipline_learner_choice.dart';

// **************************************************************************
// JsonSerializableGenerator
// **************************************************************************

DisciplineLearnerChoice _$DisciplineLearnerChoiceFromJson(
  Map<String, dynamic> json,
) => $checkedCreate(
  'DisciplineLearnerChoice',
  json,
  ($checkedConvert) {
    $checkKeys(
      json,
      requiredKeys: const ['learner_id', 'name', 'grade_level_id'],
    );
    final val = DisciplineLearnerChoice(
      learnerId: $checkedConvert('learner_id', (v) => (v as num).toInt()),
      name: $checkedConvert('name', (v) => v as String),
      gradeLevelId: $checkedConvert(
        'grade_level_id',
        (v) => (v as num).toInt(),
      ),
    );
    return val;
  },
  fieldKeyMap: const {
    'learnerId': 'learner_id',
    'gradeLevelId': 'grade_level_id',
  },
);

Map<String, dynamic> _$DisciplineLearnerChoiceToJson(
  DisciplineLearnerChoice instance,
) => <String, dynamic>{
  'learner_id': instance.learnerId,
  'name': instance.name,
  'grade_level_id': instance.gradeLevelId,
};
