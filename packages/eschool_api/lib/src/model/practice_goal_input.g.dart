// GENERATED CODE - DO NOT MODIFY BY HAND

part of 'practice_goal_input.dart';

// **************************************************************************
// JsonSerializableGenerator
// **************************************************************************

PracticeGoalInput _$PracticeGoalInputFromJson(Map<String, dynamic> json) =>
    $checkedCreate(
      'PracticeGoalInput',
      json,
      ($checkedConvert) {
        $checkKeys(
          json,
          requiredKeys: const ['operation_id', 'revision', 'days_per_week'],
        );
        final val = PracticeGoalInput(
          operationId: $checkedConvert('operation_id', (v) => v as String),
          revision: $checkedConvert('revision', (v) => (v as num).toInt()),
          daysPerWeek: $checkedConvert(
            'days_per_week',
            (v) => (v as num?)?.toInt(),
          ),
        );
        return val;
      },
      fieldKeyMap: const {
        'operationId': 'operation_id',
        'daysPerWeek': 'days_per_week',
      },
    );

Map<String, dynamic> _$PracticeGoalInputToJson(PracticeGoalInput instance) =>
    <String, dynamic>{
      'operation_id': instance.operationId,
      'revision': instance.revision,
      'days_per_week': instance.daysPerWeek,
    };
