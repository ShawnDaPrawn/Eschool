// GENERATED CODE - DO NOT MODIFY BY HAND

part of 'learner_note_detail_response.dart';

// **************************************************************************
// JsonSerializableGenerator
// **************************************************************************

LearnerNoteDetailResponse _$LearnerNoteDetailResponseFromJson(
  Map<String, dynamic> json,
) => $checkedCreate('LearnerNoteDetailResponse', json, ($checkedConvert) {
  $checkKeys(json, requiredKeys: const ['error', 'message', 'code', 'data']);
  final val = LearnerNoteDetailResponse(
    error: $checkedConvert('error', (v) => v as bool),
    message: $checkedConvert('message', (v) => v as String),
    code: $checkedConvert('code', (v) => (v as num).toInt()),
    data: $checkedConvert(
      'data',
      (v) => LearnerNoteDetail.fromJson(v as Map<String, dynamic>),
    ),
  );
  return val;
});

Map<String, dynamic> _$LearnerNoteDetailResponseToJson(
  LearnerNoteDetailResponse instance,
) => <String, dynamic>{
  'error': instance.error,
  'message': instance.message,
  'code': instance.code,
  'data': instance.data.toJson(),
};
