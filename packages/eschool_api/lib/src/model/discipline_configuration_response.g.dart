// GENERATED CODE - DO NOT MODIFY BY HAND

part of 'discipline_configuration_response.dart';

// **************************************************************************
// JsonSerializableGenerator
// **************************************************************************

DisciplineConfigurationResponse _$DisciplineConfigurationResponseFromJson(
  Map<String, dynamic> json,
) => $checkedCreate('DisciplineConfigurationResponse', json, ($checkedConvert) {
  $checkKeys(json, requiredKeys: const ['error', 'message', 'code', 'data']);
  final val = DisciplineConfigurationResponse(
    error: $checkedConvert('error', (v) => v as bool),
    message: $checkedConvert('message', (v) => v as String),
    code: $checkedConvert('code', (v) => (v as num).toInt()),
    data: $checkedConvert(
      'data',
      (v) => DisciplineConfiguration.fromJson(v as Map<String, dynamic>),
    ),
  );
  return val;
});

Map<String, dynamic> _$DisciplineConfigurationResponseToJson(
  DisciplineConfigurationResponse instance,
) => <String, dynamic>{
  'error': instance.error,
  'message': instance.message,
  'code': instance.code,
  'data': instance.data.toJson(),
};
