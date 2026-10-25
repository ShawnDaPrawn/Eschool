// GENERATED CODE - DO NOT MODIFY BY HAND

part of 'discipline_detention_enroll_write.dart';

// **************************************************************************
// JsonSerializableGenerator
// **************************************************************************

DisciplineDetentionEnrollWrite _$DisciplineDetentionEnrollWriteFromJson(
  Map<String, dynamic> json,
) => $checkedCreate(
  'DisciplineDetentionEnrollWrite',
  json,
  ($checkedConvert) {
    $checkKeys(
      json,
      requiredKeys: const ['operation_id', 'record_id', 'expected_revision'],
    );
    final val = DisciplineDetentionEnrollWrite(
      operationId: $checkedConvert('operation_id', (v) => v as String),
      recordId: $checkedConvert('record_id', (v) => (v as num).toInt()),
      expectedRevision: $checkedConvert(
        'expected_revision',
        (v) => (v as num).toInt(),
      ),
    );
    return val;
  },
  fieldKeyMap: const {
    'operationId': 'operation_id',
    'recordId': 'record_id',
    'expectedRevision': 'expected_revision',
  },
);

Map<String, dynamic> _$DisciplineDetentionEnrollWriteToJson(
  DisciplineDetentionEnrollWrite instance,
) => <String, dynamic>{
  'operation_id': instance.operationId,
  'record_id': instance.recordId,
  'expected_revision': instance.expectedRevision,
};
