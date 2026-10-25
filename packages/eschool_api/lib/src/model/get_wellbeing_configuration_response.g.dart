// GENERATED CODE - DO NOT MODIFY BY HAND

part of 'get_wellbeing_configuration_response.dart';

// **************************************************************************
// JsonSerializableGenerator
// **************************************************************************

GetWellbeingConfigurationResponse _$GetWellbeingConfigurationResponseFromJson(
  Map<String, dynamic> json,
) => $checkedCreate('GetWellbeingConfigurationResponse', json, (
  $checkedConvert,
) {
  $checkKeys(json, requiredKeys: const ['error', 'message', 'data', 'code']);
  final val = GetWellbeingConfigurationResponse(
    error: $checkedConvert('error', (v) => v as bool),
    message: $checkedConvert('message', (v) => v as String),
    data: $checkedConvert(
      'data',
      (v) => WellbeingConfiguration.fromJson(v as Map<String, dynamic>),
    ),
    code: $checkedConvert('code', (v) => (v as num).toInt()),
  );
  return val;
});

Map<String, dynamic> _$GetWellbeingConfigurationResponseToJson(
  GetWellbeingConfigurationResponse instance,
) => <String, dynamic>{
  'error': instance.error,
  'message': instance.message,
  'data': instance.data.toJson(),
  'code': instance.code,
};
