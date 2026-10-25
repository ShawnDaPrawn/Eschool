// GENERATED CODE - DO NOT MODIFY BY HAND

part of 'discipline_acknowledge_write.dart';

// **************************************************************************
// JsonSerializableGenerator
// **************************************************************************

DisciplineAcknowledgeWrite _$DisciplineAcknowledgeWriteFromJson(
  Map<String, dynamic> json,
) => $checkedCreate(
  'DisciplineAcknowledgeWrite',
  json,
  ($checkedConvert) {
    $checkKeys(json, requiredKeys: const ['operation_id', 'expected_revision']);
    final val = DisciplineAcknowledgeWrite(
      operationId: $checkedConvert('operation_id', (v) => v as String),
      expectedRevision: $checkedConvert(
        'expected_revision',
        (v) => (v as num).toInt(),
      ),
    );
    return val;
  },
  fieldKeyMap: const {
    'operationId': 'operation_id',
    'expectedRevision': 'expected_revision',
  },
);

Map<String, dynamic> _$DisciplineAcknowledgeWriteToJson(
  DisciplineAcknowledgeWrite instance,
) => <String, dynamic>{
  'operation_id': instance.operationId,
  'expected_revision': instance.expectedRevision,
};
