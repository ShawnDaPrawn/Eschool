// GENERATED CODE - DO NOT MODIFY BY HAND

part of 'academic_stored_question.dart';

// **************************************************************************
// JsonSerializableGenerator
// **************************************************************************

AcademicStoredQuestion _$AcademicStoredQuestionFromJson(
  Map<String, dynamic> json,
) => $checkedCreate('AcademicStoredQuestion', json, ($checkedConvert) {
  $checkKeys(json, requiredKeys: const ['canonical_question_id', 'mark']);
  final val = AcademicStoredQuestion(
    canonicalQuestionId: $checkedConvert(
      'canonical_question_id',
      (v) => (v as num).toInt(),
    ),
    mark: $checkedConvert('mark', (v) => v as String?),
  );
  return val;
}, fieldKeyMap: const {'canonicalQuestionId': 'canonical_question_id'});

Map<String, dynamic> _$AcademicStoredQuestionToJson(
  AcademicStoredQuestion instance,
) => <String, dynamic>{
  'canonical_question_id': instance.canonicalQuestionId,
  'mark': instance.mark,
};
