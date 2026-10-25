// GENERATED CODE - DO NOT MODIFY BY HAND

part of 'learner_note_assignment_response.dart';

// **************************************************************************
// JsonSerializableGenerator
// **************************************************************************

LearnerNoteAssignmentResponse _$LearnerNoteAssignmentResponseFromJson(
  Map<String, dynamic> json,
) => $checkedCreate('LearnerNoteAssignmentResponse', json, ($checkedConvert) {
  $checkKeys(json, requiredKeys: const ['error', 'message', 'code', 'data']);
  final val = LearnerNoteAssignmentResponse(
    error: $checkedConvert('error', (v) => v as bool),
    message: $checkedConvert('message', (v) => v as String),
    code: $checkedConvert('code', (v) => (v as num).toInt()),
    data: $checkedConvert(
      'data',
      (v) => LearnerNoteAssignment.fromJson(v as Map<String, dynamic>),
    ),
  );
  return val;
});

Map<String, dynamic> _$LearnerNoteAssignmentResponseToJson(
  LearnerNoteAssignmentResponse instance,
) => <String, dynamic>{
  'error': instance.error,
  'message': instance.message,
  'code': instance.code,
  'data': instance.data.toJson(),
};
