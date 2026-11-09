// GENERATED CODE - DO NOT MODIFY BY HAND

part of 'mark_practice_answer_response.dart';

// **************************************************************************
// JsonSerializableGenerator
// **************************************************************************

MarkPracticeAnswerResponse _$MarkPracticeAnswerResponseFromJson(
  Map<String, dynamic> json,
) => $checkedCreate('MarkPracticeAnswerResponse', json, ($checkedConvert) {
  $checkKeys(json, requiredKeys: const ['error', 'message', 'data', 'code']);
  final val = MarkPracticeAnswerResponse(
    error: $checkedConvert('error', (v) => v as bool),
    message: $checkedConvert('message', (v) => v as String),
    data: $checkedConvert(
      'data',
      (v) => PracticeMarkReceipt.fromJson(v as Map<String, dynamic>),
    ),
    code: $checkedConvert('code', (v) => (v as num).toInt()),
  );
  return val;
});

Map<String, dynamic> _$MarkPracticeAnswerResponseToJson(
  MarkPracticeAnswerResponse instance,
) => <String, dynamic>{
  'error': instance.error,
  'message': instance.message,
  'data': instance.data.toJson(),
  'code': instance.code,
};
