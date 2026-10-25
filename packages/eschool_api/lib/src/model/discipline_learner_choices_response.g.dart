// GENERATED CODE - DO NOT MODIFY BY HAND

part of 'discipline_learner_choices_response.dart';

// **************************************************************************
// JsonSerializableGenerator
// **************************************************************************

DisciplineLearnerChoicesResponse _$DisciplineLearnerChoicesResponseFromJson(
  Map<String, dynamic> json,
) => $checkedCreate('DisciplineLearnerChoicesResponse', json, (
  $checkedConvert,
) {
  $checkKeys(json, requiredKeys: const ['error', 'message', 'code', 'data']);
  final val = DisciplineLearnerChoicesResponse(
    error: $checkedConvert('error', (v) => v as bool),
    message: $checkedConvert('message', (v) => v as String),
    code: $checkedConvert('code', (v) => (v as num).toInt()),
    data: $checkedConvert(
      'data',
      (v) => DisciplineLearnerChoices.fromJson(v as Map<String, dynamic>),
    ),
  );
  return val;
});

Map<String, dynamic> _$DisciplineLearnerChoicesResponseToJson(
  DisciplineLearnerChoicesResponse instance,
) => <String, dynamic>{
  'error': instance.error,
  'message': instance.message,
  'code': instance.code,
  'data': instance.data.toJson(),
};
