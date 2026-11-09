// GENERATED CODE - DO NOT MODIFY BY HAND

part of 'learning_path_item.dart';

// **************************************************************************
// JsonSerializableGenerator
// **************************************************************************

LearningPathItem _$LearningPathItemFromJson(Map<String, dynamic> json) =>
    $checkedCreate('LearningPathItem', json, ($checkedConvert) {
      $checkKeys(
        json,
        requiredKeys: const [
          'id',
          'purpose',
          'position',
          'question',
          'answered',
          'correct',
          'explanation',
        ],
      );
      final val = LearningPathItem(
        id: $checkedConvert('id', (v) => (v as num).toInt()),
        purpose: $checkedConvert(
          'purpose',
          (v) => $enumDecode(
            _$LearningPathItemPurposeEnumEnumMap,
            v,
            unknownValue: LearningPathItemPurposeEnum.unknownDefaultOpenApi,
          ),
        ),
        position: $checkedConvert('position', (v) => (v as num).toInt()),
        question: $checkedConvert(
          'question',
          (v) => LearningPathItemQuestion.fromJson(v as Map<String, dynamic>),
        ),
        answered: $checkedConvert('answered', (v) => v as bool),
        correct: $checkedConvert('correct', (v) => v as bool?),
        explanation: $checkedConvert('explanation', (v) => v as String?),
      );
      return val;
    });

Map<String, dynamic> _$LearningPathItemToJson(LearningPathItem instance) =>
    <String, dynamic>{
      'id': instance.id,
      'purpose': _$LearningPathItemPurposeEnumEnumMap[instance.purpose]!,
      'position': instance.position,
      'question': instance.question.toJson(),
      'answered': instance.answered,
      'correct': instance.correct,
      'explanation': instance.explanation,
    };

const _$LearningPathItemPurposeEnumEnumMap = {
  LearningPathItemPurposeEnum.practice: 'practice',
  LearningPathItemPurposeEnum.check: 'check',
  LearningPathItemPurposeEnum.unknownDefaultOpenApi: 'unknown_default_open_api',
};
