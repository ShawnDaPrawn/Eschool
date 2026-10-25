// GENERATED CODE - DO NOT MODIFY BY HAND

part of 'learner_note_assignment_revoke.dart';

// **************************************************************************
// JsonSerializableGenerator
// **************************************************************************

LearnerNoteAssignmentRevoke _$LearnerNoteAssignmentRevokeFromJson(
  Map<String, dynamic> json,
) => $checkedCreate(
  'LearnerNoteAssignmentRevoke',
  json,
  ($checkedConvert) {
    $checkKeys(json, requiredKeys: const ['operation_id', 'expected_revision']);
    final val = LearnerNoteAssignmentRevoke(
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

Map<String, dynamic> _$LearnerNoteAssignmentRevokeToJson(
  LearnerNoteAssignmentRevoke instance,
) => <String, dynamic>{
  'operation_id': instance.operationId,
  'expected_revision': instance.expectedRevision,
};
