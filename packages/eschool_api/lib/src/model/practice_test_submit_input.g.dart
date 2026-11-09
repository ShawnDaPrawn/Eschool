// GENERATED CODE - DO NOT MODIFY BY HAND

part of 'practice_test_submit_input.dart';

// **************************************************************************
// JsonSerializableGenerator
// **************************************************************************

PracticeTestSubmitInput _$PracticeTestSubmitInputFromJson(
  Map<String, dynamic> json,
) => $checkedCreate('PracticeTestSubmitInput', json, ($checkedConvert) {
  $checkKeys(json, requiredKeys: const ['operation_id']);
  final val = PracticeTestSubmitInput(
    operationId: $checkedConvert('operation_id', (v) => v as String),
    answers: $checkedConvert(
      'answers',
      (v) => (v as List<dynamic>?)
          ?.map((e) => PracticeTestAnswer.fromJson(e as Map<String, dynamic>))
          .toList(),
    ),
  );
  return val;
}, fieldKeyMap: const {'operationId': 'operation_id'});

Map<String, dynamic> _$PracticeTestSubmitInputToJson(
  PracticeTestSubmitInput instance,
) => <String, dynamic>{
  'operation_id': instance.operationId,
  'answers': ?instance.answers?.map((e) => e.toJson()).toList(),
};
