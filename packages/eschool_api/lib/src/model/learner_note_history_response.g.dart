// GENERATED CODE - DO NOT MODIFY BY HAND

part of 'learner_note_history_response.dart';

// **************************************************************************
// JsonSerializableGenerator
// **************************************************************************

LearnerNoteHistoryResponse _$LearnerNoteHistoryResponseFromJson(
  Map<String, dynamic> json,
) => $checkedCreate('LearnerNoteHistoryResponse', json, ($checkedConvert) {
  $checkKeys(json, requiredKeys: const ['error', 'message', 'code', 'data']);
  final val = LearnerNoteHistoryResponse(
    error: $checkedConvert('error', (v) => v as bool),
    message: $checkedConvert('message', (v) => v as String),
    code: $checkedConvert('code', (v) => (v as num).toInt()),
    data: $checkedConvert(
      'data',
      (v) => LearnerNoteHistory.fromJson(v as Map<String, dynamic>),
    ),
  );
  return val;
});

Map<String, dynamic> _$LearnerNoteHistoryResponseToJson(
  LearnerNoteHistoryResponse instance,
) => <String, dynamic>{
  'error': instance.error,
  'message': instance.message,
  'code': instance.code,
  'data': instance.data.toJson(),
};
