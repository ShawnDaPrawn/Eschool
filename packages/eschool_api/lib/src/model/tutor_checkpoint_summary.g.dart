// GENERATED CODE - DO NOT MODIFY BY HAND

part of 'tutor_checkpoint_summary.dart';

// **************************************************************************
// JsonSerializableGenerator
// **************************************************************************

TutorCheckpointSummary _$TutorCheckpointSummaryFromJson(
  Map<String, dynamic> json,
) => $checkedCreate('TutorCheckpointSummary', json, ($checkedConvert) {
  $checkKeys(json, requiredKeys: const ['id', 'operation_id', 'state']);
  final val = TutorCheckpointSummary(
    id: $checkedConvert('id', (v) => v as String),
    operationId: $checkedConvert('operation_id', (v) => v as String),
    state: $checkedConvert(
      'state',
      (v) => $enumDecode(
        _$TutorCheckpointSummaryStateEnumEnumMap,
        v,
        unknownValue: TutorCheckpointSummaryStateEnum.unknownDefaultOpenApi,
      ),
    ),
  );
  return val;
}, fieldKeyMap: const {'operationId': 'operation_id'});

Map<String, dynamic> _$TutorCheckpointSummaryToJson(
  TutorCheckpointSummary instance,
) => <String, dynamic>{
  'id': instance.id,
  'operation_id': instance.operationId,
  'state': _$TutorCheckpointSummaryStateEnumEnumMap[instance.state]!,
};

const _$TutorCheckpointSummaryStateEnumEnumMap = {
  TutorCheckpointSummaryStateEnum.ready: 'ready',
  TutorCheckpointSummaryStateEnum.answered: 'answered',
  TutorCheckpointSummaryStateEnum.unknownDefaultOpenApi:
      'unknown_default_open_api',
};
