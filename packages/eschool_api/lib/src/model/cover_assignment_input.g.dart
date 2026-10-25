// GENERATED CODE - DO NOT MODIFY BY HAND

part of 'cover_assignment_input.dart';

// **************************************************************************
// JsonSerializableGenerator
// **************************************************************************

CoverAssignmentInput _$CoverAssignmentInputFromJson(
  Map<String, dynamic> json,
) => $checkedCreate(
  'CoverAssignmentInput',
  json,
  ($checkedConvert) {
    $checkKeys(
      json,
      requiredKeys: const ['operation_id', 'revision', 'substitute_teacher_id'],
    );
    final val = CoverAssignmentInput(
      operationId: $checkedConvert('operation_id', (v) => v as String),
      revision: $checkedConvert('revision', (v) => (v as num).toInt()),
      substituteTeacherId: $checkedConvert(
        'substitute_teacher_id',
        (v) => (v as num).toInt(),
      ),
    );
    return val;
  },
  fieldKeyMap: const {
    'operationId': 'operation_id',
    'substituteTeacherId': 'substitute_teacher_id',
  },
);

Map<String, dynamic> _$CoverAssignmentInputToJson(
  CoverAssignmentInput instance,
) => <String, dynamic>{
  'operation_id': instance.operationId,
  'revision': instance.revision,
  'substitute_teacher_id': instance.substituteTeacherId,
};
