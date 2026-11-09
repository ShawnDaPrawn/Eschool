// GENERATED CODE - DO NOT MODIFY BY HAND

part of 'save_practice_test_answers_response.dart';

// **************************************************************************
// JsonSerializableGenerator
// **************************************************************************

SavePracticeTestAnswersResponse _$SavePracticeTestAnswersResponseFromJson(
  Map<String, dynamic> json,
) => $checkedCreate('SavePracticeTestAnswersResponse', json, ($checkedConvert) {
  $checkKeys(json, requiredKeys: const ['error', 'message', 'data', 'code']);
  final val = SavePracticeTestAnswersResponse(
    error: $checkedConvert('error', (v) => v as bool),
    message: $checkedConvert('message', (v) => v as String),
    data: $checkedConvert(
      'data',
      (v) => PracticeTestAnswerReceipt.fromJson(v as Map<String, dynamic>),
    ),
    code: $checkedConvert('code', (v) => (v as num).toInt()),
  );
  return val;
});

Map<String, dynamic> _$SavePracticeTestAnswersResponseToJson(
  SavePracticeTestAnswersResponse instance,
) => <String, dynamic>{
  'error': instance.error,
  'message': instance.message,
  'data': instance.data.toJson(),
  'code': instance.code,
};
