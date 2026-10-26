// GENERATED CODE - DO NOT MODIFY BY HAND

part of 'exam_coach_revision_write.dart';

// **************************************************************************
// JsonSerializableGenerator
// **************************************************************************

ExamCoachRevisionWrite _$ExamCoachRevisionWriteFromJson(
  Map<String, dynamic> json,
) => $checkedCreate(
  'ExamCoachRevisionWrite',
  json,
  ($checkedConvert) {
    $checkKeys(json, requiredKeys: const ['operation_id', 'expected_revision']);
    final val = ExamCoachRevisionWrite(
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

Map<String, dynamic> _$ExamCoachRevisionWriteToJson(
  ExamCoachRevisionWrite instance,
) => <String, dynamic>{
  'operation_id': instance.operationId,
  'expected_revision': instance.expectedRevision,
};
