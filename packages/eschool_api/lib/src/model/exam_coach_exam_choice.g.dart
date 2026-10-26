// GENERATED CODE - DO NOT MODIFY BY HAND

part of 'exam_coach_exam_choice.dart';

// **************************************************************************
// JsonSerializableGenerator
// **************************************************************************

ExamCoachExamChoice _$ExamCoachExamChoiceFromJson(Map<String, dynamic> json) =>
    $checkedCreate(
      'ExamCoachExamChoice',
      json,
      ($checkedConvert) {
        $checkKeys(
          json,
          requiredKeys: const ['id', 'name', 'exam_on', 'schedule_signature'],
        );
        final val = ExamCoachExamChoice(
          id: $checkedConvert('id', (v) => (v as num).toInt()),
          name: $checkedConvert('name', (v) => v as String),
          examOn: $checkedConvert('exam_on', (v) => v as String),
          scheduleSignature: $checkedConvert(
            'schedule_signature',
            (v) => v as String,
          ),
        );
        return val;
      },
      fieldKeyMap: const {
        'examOn': 'exam_on',
        'scheduleSignature': 'schedule_signature',
      },
    );

Map<String, dynamic> _$ExamCoachExamChoiceToJson(
  ExamCoachExamChoice instance,
) => <String, dynamic>{
  'id': instance.id,
  'name': instance.name,
  'exam_on': instance.examOn,
  'schedule_signature': instance.scheduleSignature,
};
