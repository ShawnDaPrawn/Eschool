// GENERATED CODE - DO NOT MODIFY BY HAND

part of 'learner_note_list_response.dart';

// **************************************************************************
// JsonSerializableGenerator
// **************************************************************************

LearnerNoteListResponse _$LearnerNoteListResponseFromJson(
  Map<String, dynamic> json,
) => $checkedCreate('LearnerNoteListResponse', json, ($checkedConvert) {
  $checkKeys(json, requiredKeys: const ['error', 'message', 'code', 'data']);
  final val = LearnerNoteListResponse(
    error: $checkedConvert('error', (v) => v as bool),
    message: $checkedConvert('message', (v) => v as String),
    code: $checkedConvert('code', (v) => (v as num).toInt()),
    data: $checkedConvert(
      'data',
      (v) => LearnerNoteList.fromJson(v as Map<String, dynamic>),
    ),
  );
  return val;
});

Map<String, dynamic> _$LearnerNoteListResponseToJson(
  LearnerNoteListResponse instance,
) => <String, dynamic>{
  'error': instance.error,
  'message': instance.message,
  'code': instance.code,
  'data': instance.data.toJson(),
};
