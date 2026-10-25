// GENERATED CODE - DO NOT MODIFY BY HAND

part of 'learner_note_assignments.dart';

// **************************************************************************
// JsonSerializableGenerator
// **************************************************************************

LearnerNoteAssignments _$LearnerNoteAssignmentsFromJson(
  Map<String, dynamic> json,
) => $checkedCreate('LearnerNoteAssignments', json, ($checkedConvert) {
  $checkKeys(json, requiredKeys: const ['learner_id', 'assignments']);
  final val = LearnerNoteAssignments(
    learnerId: $checkedConvert('learner_id', (v) => (v as num).toInt()),
    assignments: $checkedConvert(
      'assignments',
      (v) => (v as List<dynamic>)
          .map((e) => LearnerNoteAssignment.fromJson(e as Map<String, dynamic>))
          .toList(),
    ),
  );
  return val;
}, fieldKeyMap: const {'learnerId': 'learner_id'});

Map<String, dynamic> _$LearnerNoteAssignmentsToJson(
  LearnerNoteAssignments instance,
) => <String, dynamic>{
  'learner_id': instance.learnerId,
  'assignments': instance.assignments.map((e) => e.toJson()).toList(),
};
