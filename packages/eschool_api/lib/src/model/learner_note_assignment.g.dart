// GENERATED CODE - DO NOT MODIFY BY HAND

part of 'learner_note_assignment.dart';

// **************************************************************************
// JsonSerializableGenerator
// **************************************************************************

LearnerNoteAssignment _$LearnerNoteAssignmentFromJson(
  Map<String, dynamic> json,
) => $checkedCreate(
  'LearnerNoteAssignment',
  json,
  ($checkedConvert) {
    $checkKeys(
      json,
      requiredKeys: const [
        'id',
        'learner_id',
        'staff_user_id',
        'staff_name',
        'starts_on',
        'ends_on',
        'revision',
      ],
    );
    final val = LearnerNoteAssignment(
      id: $checkedConvert('id', (v) => (v as num).toInt()),
      learnerId: $checkedConvert('learner_id', (v) => (v as num).toInt()),
      staffUserId: $checkedConvert('staff_user_id', (v) => (v as num).toInt()),
      staffName: $checkedConvert('staff_name', (v) => v as String),
      startsOn: $checkedConvert('starts_on', (v) => v as String),
      endsOn: $checkedConvert('ends_on', (v) => v as String),
      revision: $checkedConvert('revision', (v) => (v as num).toInt()),
      revoked: $checkedConvert('revoked', (v) => v as bool?),
    );
    return val;
  },
  fieldKeyMap: const {
    'learnerId': 'learner_id',
    'staffUserId': 'staff_user_id',
    'staffName': 'staff_name',
    'startsOn': 'starts_on',
    'endsOn': 'ends_on',
  },
);

Map<String, dynamic> _$LearnerNoteAssignmentToJson(
  LearnerNoteAssignment instance,
) => <String, dynamic>{
  'id': instance.id,
  'learner_id': instance.learnerId,
  'staff_user_id': instance.staffUserId,
  'staff_name': instance.staffName,
  'starts_on': instance.startsOn,
  'ends_on': instance.endsOn,
  'revision': instance.revision,
  'revoked': ?instance.revoked,
};
