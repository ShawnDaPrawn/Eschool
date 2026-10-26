// GENERATED CODE - DO NOT MODIFY BY HAND

part of 'exam_coach_configuration_response.dart';

// **************************************************************************
// JsonSerializableGenerator
// **************************************************************************

ExamCoachConfigurationResponse _$ExamCoachConfigurationResponseFromJson(
  Map<String, dynamic> json,
) => $checkedCreate('ExamCoachConfigurationResponse', json, ($checkedConvert) {
  $checkKeys(json, requiredKeys: const ['error', 'message', 'data', 'code']);
  final val = ExamCoachConfigurationResponse(
    error: $checkedConvert('error', (v) => v as bool),
    message: $checkedConvert('message', (v) => v as String),
    data: $checkedConvert(
      'data',
      (v) => ExamCoachConfiguration.fromJson(v as Map<String, dynamic>),
    ),
    code: $checkedConvert('code', (v) => (v as num).toInt()),
  );
  return val;
});

Map<String, dynamic> _$ExamCoachConfigurationResponseToJson(
  ExamCoachConfigurationResponse instance,
) => <String, dynamic>{
  'error': instance.error,
  'message': instance.message,
  'data': instance.data.toJson(),
  'code': instance.code,
};
