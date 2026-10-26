// GENERATED CODE - DO NOT MODIFY BY HAND

part of 'exam_coach_exam_dates.dart';

// **************************************************************************
// JsonSerializableGenerator
// **************************************************************************

ExamCoachExamDates _$ExamCoachExamDatesFromJson(Map<String, dynamic> json) =>
    $checkedCreate('ExamCoachExamDates', json, ($checkedConvert) {
      $checkKeys(json, requiredKeys: const ['group_id', 'exams']);
      final val = ExamCoachExamDates(
        groupId: $checkedConvert('group_id', (v) => (v as num).toInt()),
        exams: $checkedConvert(
          'exams',
          (v) => (v as List<dynamic>)
              .map(
                (e) => ExamCoachExamChoice.fromJson(e as Map<String, dynamic>),
              )
              .toList(),
        ),
      );
      return val;
    }, fieldKeyMap: const {'groupId': 'group_id'});

Map<String, dynamic> _$ExamCoachExamDatesToJson(ExamCoachExamDates instance) =>
    <String, dynamic>{
      'group_id': instance.groupId,
      'exams': instance.exams.map((e) => e.toJson()).toList(),
    };
