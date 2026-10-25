// GENERATED CODE - DO NOT MODIFY BY HAND

part of 'learner_note_assignments_response.dart';

// **************************************************************************
// JsonSerializableGenerator
// **************************************************************************

LearnerNoteAssignmentsResponse _$LearnerNoteAssignmentsResponseFromJson(
  Map<String, dynamic> json,
) => $checkedCreate('LearnerNoteAssignmentsResponse', json, ($checkedConvert) {
  $checkKeys(json, requiredKeys: const ['error', 'message', 'code', 'data']);
  final val = LearnerNoteAssignmentsResponse(
    error: $checkedConvert('error', (v) => v as bool),
    message: $checkedConvert('message', (v) => v as String),
    code: $checkedConvert('code', (v) => (v as num).toInt()),
    data: $checkedConvert(
      'data',
      (v) => LearnerNoteAssignments.fromJson(v as Map<String, dynamic>),
    ),
  );
  return val;
});

Map<String, dynamic> _$LearnerNoteAssignmentsResponseToJson(
  LearnerNoteAssignmentsResponse instance,
) => <String, dynamic>{
  'error': instance.error,
  'message': instance.message,
  'code': instance.code,
  'data': instance.data.toJson(),
};
