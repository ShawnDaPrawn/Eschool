// GENERATED CODE - DO NOT MODIFY BY HAND

part of 'discipline_detention_cancel_write.dart';

// **************************************************************************
// JsonSerializableGenerator
// **************************************************************************

DisciplineDetentionCancelWrite _$DisciplineDetentionCancelWriteFromJson(
  Map<String, dynamic> json,
) => $checkedCreate(
  'DisciplineDetentionCancelWrite',
  json,
  ($checkedConvert) {
    $checkKeys(
      json,
      requiredKeys: const ['operation_id', 'expected_revision', 'reason'],
    );
    final val = DisciplineDetentionCancelWrite(
      operationId: $checkedConvert('operation_id', (v) => v as String),
      expectedRevision: $checkedConvert(
        'expected_revision',
        (v) => (v as num).toInt(),
      ),
      reason: $checkedConvert('reason', (v) => v as String),
    );
    return val;
  },
  fieldKeyMap: const {
    'operationId': 'operation_id',
    'expectedRevision': 'expected_revision',
  },
);

Map<String, dynamic> _$DisciplineDetentionCancelWriteToJson(
  DisciplineDetentionCancelWrite instance,
) => <String, dynamic>{
  'operation_id': instance.operationId,
  'expected_revision': instance.expectedRevision,
  'reason': instance.reason,
};
