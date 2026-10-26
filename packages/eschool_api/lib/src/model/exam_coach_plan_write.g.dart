// GENERATED CODE - DO NOT MODIFY BY HAND

part of 'exam_coach_plan_write.dart';

// **************************************************************************
// JsonSerializableGenerator
// **************************************************************************

ExamCoachPlanWrite _$ExamCoachPlanWriteFromJson(Map<String, dynamic> json) =>
    $checkedCreate(
      'ExamCoachPlanWrite',
      json,
      ($checkedConvert) {
        $checkKeys(
          json,
          requiredKeys: const [
            'operation_id',
            'exam_timetable_id',
            'schedule_signature',
            'paper_version_ids',
            'study_minutes',
          ],
        );
        final val = ExamCoachPlanWrite(
          operationId: $checkedConvert('operation_id', (v) => v as String),
          examTimetableId: $checkedConvert(
            'exam_timetable_id',
            (v) => (v as num).toInt(),
          ),
          scheduleSignature: $checkedConvert(
            'schedule_signature',
            (v) => v as String,
          ),
          paperVersionIds: $checkedConvert(
            'paper_version_ids',
            (v) => (v as List<dynamic>).map((e) => e as String).toList(),
          ),
          studyMinutes: $checkedConvert(
            'study_minutes',
            (v) => (v as num).toInt(),
          ),
        );
        return val;
      },
      fieldKeyMap: const {
        'operationId': 'operation_id',
        'examTimetableId': 'exam_timetable_id',
        'scheduleSignature': 'schedule_signature',
        'paperVersionIds': 'paper_version_ids',
        'studyMinutes': 'study_minutes',
      },
    );

Map<String, dynamic> _$ExamCoachPlanWriteToJson(ExamCoachPlanWrite instance) =>
    <String, dynamic>{
      'operation_id': instance.operationId,
      'exam_timetable_id': instance.examTimetableId,
      'schedule_signature': instance.scheduleSignature,
      'paper_version_ids': instance.paperVersionIds,
      'study_minutes': instance.studyMinutes,
    };
