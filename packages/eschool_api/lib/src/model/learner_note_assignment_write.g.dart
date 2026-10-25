// GENERATED CODE - DO NOT MODIFY BY HAND

part of 'learner_note_assignment_write.dart';

// **************************************************************************
// JsonSerializableGenerator
// **************************************************************************

LearnerNoteAssignmentWrite _$LearnerNoteAssignmentWriteFromJson(
  Map<String, dynamic> json,
) => $checkedCreate(
  'LearnerNoteAssignmentWrite',
  json,
  ($checkedConvert) {
    $checkKeys(
      json,
      requiredKeys: const [
        'operation_id',
        'expected_revision',
        'starts_on',
        'ends_on',
      ],
    );
    final val = LearnerNoteAssignmentWrite(
      operationId: $checkedConvert('operation_id', (v) => v as String),
      expectedRevision: $checkedConvert(
        'expected_revision',
        (v) => (v as num).toInt(),
      ),
      startsOn: $checkedConvert('starts_on', (v) => v as String),
      endsOn: $checkedConvert('ends_on', (v) => v as String),
    );
    return val;
  },
  fieldKeyMap: const {
    'operationId': 'operation_id',
    'expectedRevision': 'expected_revision',
    'startsOn': 'starts_on',
    'endsOn': 'ends_on',
  },
);

Map<String, dynamic> _$LearnerNoteAssignmentWriteToJson(
  LearnerNoteAssignmentWrite instance,
) => <String, dynamic>{
  'operation_id': instance.operationId,
  'expected_revision': instance.expectedRevision,
  'starts_on': instance.startsOn,
  'ends_on': instance.endsOn,
};
