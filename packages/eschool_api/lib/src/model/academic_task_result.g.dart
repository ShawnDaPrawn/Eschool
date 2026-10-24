// GENERATED CODE - DO NOT MODIFY BY HAND

part of 'academic_task_result.dart';

// **************************************************************************
// JsonSerializableGenerator
// **************************************************************************

AcademicTaskResult _$AcademicTaskResultFromJson(Map<String, dynamic> json) =>
    $checkedCreate(
      'AcademicTaskResult',
      json,
      ($checkedConvert) {
        $checkKeys(
          json,
          requiredKeys: const [
            'task_id',
            'percentage_bp',
            'disposition',
            'complete',
            'weight_bp',
          ],
        );
        final val = AcademicTaskResult(
          taskId: $checkedConvert('task_id', (v) => (v as num).toInt()),
          percentageBp: $checkedConvert(
            'percentage_bp',
            (v) => (v as num?)?.toInt(),
          ),
          disposition: $checkedConvert('disposition', (v) => v as String),
          complete: $checkedConvert('complete', (v) => v as bool),
          weightBp: $checkedConvert('weight_bp', (v) => (v as num).toInt()),
        );
        return val;
      },
      fieldKeyMap: const {
        'taskId': 'task_id',
        'percentageBp': 'percentage_bp',
        'weightBp': 'weight_bp',
      },
    );

Map<String, dynamic> _$AcademicTaskResultToJson(AcademicTaskResult instance) =>
    <String, dynamic>{
      'task_id': instance.taskId,
      'percentage_bp': instance.percentageBp,
      'disposition': instance.disposition,
      'complete': instance.complete,
      'weight_bp': instance.weightBp,
    };
