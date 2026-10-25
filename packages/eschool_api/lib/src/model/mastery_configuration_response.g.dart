// GENERATED CODE - DO NOT MODIFY BY HAND

part of 'mastery_configuration_response.dart';

// **************************************************************************
// JsonSerializableGenerator
// **************************************************************************

MasteryConfigurationResponse _$MasteryConfigurationResponseFromJson(
  Map<String, dynamic> json,
) => $checkedCreate('MasteryConfigurationResponse', json, ($checkedConvert) {
  $checkKeys(json, requiredKeys: const ['error', 'message', 'code', 'data']);
  final val = MasteryConfigurationResponse(
    error: $checkedConvert('error', (v) => v as bool),
    message: $checkedConvert('message', (v) => v as String),
    code: $checkedConvert('code', (v) => (v as num).toInt()),
    data: $checkedConvert(
      'data',
      (v) => MasteryConfiguration.fromJson(v as Map<String, dynamic>),
    ),
  );
  return val;
});

Map<String, dynamic> _$MasteryConfigurationResponseToJson(
  MasteryConfigurationResponse instance,
) => <String, dynamic>{
  'error': instance.error,
  'message': instance.message,
  'code': instance.code,
  'data': instance.data.toJson(),
};
