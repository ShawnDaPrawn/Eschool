// GENERATED CODE - DO NOT MODIFY BY HAND

part of 'exam_coach_study_day.dart';

// **************************************************************************
// JsonSerializableGenerator
// **************************************************************************

ExamCoachStudyDay _$ExamCoachStudyDayFromJson(Map<String, dynamic> json) =>
    $checkedCreate('ExamCoachStudyDay', json, ($checkedConvert) {
      $checkKeys(
        json,
        requiredKeys: const ['date', 'topic_id', 'minutes', 'topic_name'],
      );
      final val = ExamCoachStudyDay(
        date: $checkedConvert('date', (v) => v as String),
        topicId: $checkedConvert('topic_id', (v) => (v as num).toInt()),
        minutes: $checkedConvert('minutes', (v) => (v as num).toInt()),
        topicName: $checkedConvert('topic_name', (v) => v as String),
      );
      return val;
    }, fieldKeyMap: const {'topicId': 'topic_id', 'topicName': 'topic_name'});

Map<String, dynamic> _$ExamCoachStudyDayToJson(ExamCoachStudyDay instance) =>
    <String, dynamic>{
      'date': instance.date,
      'topic_id': instance.topicId,
      'minutes': instance.minutes,
      'topic_name': instance.topicName,
    };
