// GENERATED CODE - DO NOT MODIFY BY HAND

part of 'academic_component_result.dart';

// **************************************************************************
// JsonSerializableGenerator
// **************************************************************************

AcademicComponentResult _$AcademicComponentResultFromJson(
  Map<String, dynamic> json,
) => $checkedCreate(
  'AcademicComponentResult',
  json,
  ($checkedConvert) {
    $checkKeys(
      json,
      requiredKeys: const [
        'weight_bp',
        'declared_task_weight_bp',
        'excused_task_weight_bp',
        'known_task_weight_bp',
        'running_bp',
        'percentage_bp',
        'complete',
      ],
    );
    final val = AcademicComponentResult(
      weightBp: $checkedConvert('weight_bp', (v) => (v as num).toInt()),
      declaredTaskWeightBp: $checkedConvert(
        'declared_task_weight_bp',
        (v) => (v as num).toInt(),
      ),
      excusedTaskWeightBp: $checkedConvert(
        'excused_task_weight_bp',
        (v) => (v as num).toInt(),
      ),
      knownTaskWeightBp: $checkedConvert(
        'known_task_weight_bp',
        (v) => (v as num).toInt(),
      ),
      runningBp: $checkedConvert('running_bp', (v) => (v as num?)?.toInt()),
      percentageBp: $checkedConvert(
        'percentage_bp',
        (v) => (v as num?)?.toInt(),
      ),
      complete: $checkedConvert('complete', (v) => v as bool),
    );
    return val;
  },
  fieldKeyMap: const {
    'weightBp': 'weight_bp',
    'declaredTaskWeightBp': 'declared_task_weight_bp',
    'excusedTaskWeightBp': 'excused_task_weight_bp',
    'knownTaskWeightBp': 'known_task_weight_bp',
    'runningBp': 'running_bp',
    'percentageBp': 'percentage_bp',
  },
);

Map<String, dynamic> _$AcademicComponentResultToJson(
  AcademicComponentResult instance,
) => <String, dynamic>{
  'weight_bp': instance.weightBp,
  'declared_task_weight_bp': instance.declaredTaskWeightBp,
  'excused_task_weight_bp': instance.excusedTaskWeightBp,
  'known_task_weight_bp': instance.knownTaskWeightBp,
  'running_bp': instance.runningBp,
  'percentage_bp': instance.percentageBp,
  'complete': instance.complete,
};
