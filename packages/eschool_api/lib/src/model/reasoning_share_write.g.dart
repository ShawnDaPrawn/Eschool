// GENERATED CODE - DO NOT MODIFY BY HAND

part of 'reasoning_share_write.dart';

// **************************************************************************
// JsonSerializableGenerator
// **************************************************************************

ReasoningShareWrite _$ReasoningShareWriteFromJson(Map<String, dynamic> json) =>
    $checkedCreate(
      'ReasoningShareWrite',
      json,
      ($checkedConvert) {
        $checkKeys(
          json,
          requiredKeys: const ['operation_id', 'learner_id', 'text'],
        );
        final val = ReasoningShareWrite(
          operationId: $checkedConvert('operation_id', (v) => v as String),
          learnerId: $checkedConvert('learner_id', (v) => (v as num).toInt()),
          text: $checkedConvert('text', (v) => v as String),
        );
        return val;
      },
      fieldKeyMap: const {
        'operationId': 'operation_id',
        'learnerId': 'learner_id',
      },
    );

Map<String, dynamic> _$ReasoningShareWriteToJson(
  ReasoningShareWrite instance,
) => <String, dynamic>{
  'operation_id': instance.operationId,
  'learner_id': instance.learnerId,
  'text': instance.text,
};
