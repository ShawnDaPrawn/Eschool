// GENERATED CODE - DO NOT MODIFY BY HAND

part of 'academic_task_question.dart';

// **************************************************************************
// JsonSerializableGenerator
// **************************************************************************

AcademicTaskQuestion _$AcademicTaskQuestionFromJson(
  Map<String, dynamic> json,
) => $checkedCreate(
  'AcademicTaskQuestion',
  json,
  ($checkedConvert) {
    $checkKeys(
      json,
      requiredKeys: const ['id', 'canonical_question_id', 'number', 'max_mark'],
    );
    final val = AcademicTaskQuestion(
      id: $checkedConvert('id', (v) => (v as num).toInt()),
      canonicalQuestionId: $checkedConvert(
        'canonical_question_id',
        (v) => (v as num).toInt(),
      ),
      number: $checkedConvert('number', (v) => v as String),
      maxMark: $checkedConvert('max_mark', (v) => v as String),
    );
    return val;
  },
  fieldKeyMap: const {
    'canonicalQuestionId': 'canonical_question_id',
    'maxMark': 'max_mark',
  },
);

Map<String, dynamic> _$AcademicTaskQuestionToJson(
  AcademicTaskQuestion instance,
) => <String, dynamic>{
  'id': instance.id,
  'canonical_question_id': instance.canonicalQuestionId,
  'number': instance.number,
  'max_mark': instance.maxMark,
};
