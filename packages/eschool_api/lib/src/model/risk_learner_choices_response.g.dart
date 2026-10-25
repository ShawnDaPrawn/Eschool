// GENERATED CODE - DO NOT MODIFY BY HAND

part of 'risk_learner_choices_response.dart';

// **************************************************************************
// JsonSerializableGenerator
// **************************************************************************

RiskLearnerChoicesResponse _$RiskLearnerChoicesResponseFromJson(
  Map<String, dynamic> json,
) => $checkedCreate('RiskLearnerChoicesResponse', json, ($checkedConvert) {
  $checkKeys(json, requiredKeys: const ['error', 'message', 'code', 'data']);
  final val = RiskLearnerChoicesResponse(
    error: $checkedConvert('error', (v) => v as bool),
    message: $checkedConvert('message', (v) => v as String),
    code: $checkedConvert('code', (v) => (v as num).toInt()),
    data: $checkedConvert(
      'data',
      (v) => RiskLearnerChoices.fromJson(v as Map<String, dynamic>),
    ),
  );
  return val;
});

Map<String, dynamic> _$RiskLearnerChoicesResponseToJson(
  RiskLearnerChoicesResponse instance,
) => <String, dynamic>{
  'error': instance.error,
  'message': instance.message,
  'code': instance.code,
  'data': instance.data.toJson(),
};
