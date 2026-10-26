// GENERATED CODE - DO NOT MODIFY BY HAND

part of 'tutor_checkpoint_answer_write.dart';

// **************************************************************************
// JsonSerializableGenerator
// **************************************************************************

TutorCheckpointAnswerWrite _$TutorCheckpointAnswerWriteFromJson(
  Map<String, dynamic> json,
) => $checkedCreate('TutorCheckpointAnswerWrite', json, ($checkedConvert) {
  $checkKeys(json, requiredKeys: const ['operation_id', 'answer']);
  final val = TutorCheckpointAnswerWrite(
    operationId: $checkedConvert('operation_id', (v) => v as String),
    answer: $checkedConvert('answer', (v) => v as String),
  );
  return val;
}, fieldKeyMap: const {'operationId': 'operation_id'});

Map<String, dynamic> _$TutorCheckpointAnswerWriteToJson(
  TutorCheckpointAnswerWrite instance,
) => <String, dynamic>{
  'operation_id': instance.operationId,
  'answer': instance.answer,
};
