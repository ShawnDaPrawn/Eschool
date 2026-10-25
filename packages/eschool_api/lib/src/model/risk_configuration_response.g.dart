// GENERATED CODE - DO NOT MODIFY BY HAND

part of 'risk_configuration_response.dart';

// **************************************************************************
// JsonSerializableGenerator
// **************************************************************************

RiskConfigurationResponse _$RiskConfigurationResponseFromJson(
  Map<String, dynamic> json,
) => $checkedCreate('RiskConfigurationResponse', json, ($checkedConvert) {
  $checkKeys(json, requiredKeys: const ['error', 'message', 'code', 'data']);
  final val = RiskConfigurationResponse(
    error: $checkedConvert('error', (v) => v as bool),
    message: $checkedConvert('message', (v) => v as String),
    code: $checkedConvert('code', (v) => (v as num).toInt()),
    data: $checkedConvert(
      'data',
      (v) => RiskConfiguration.fromJson(v as Map<String, dynamic>),
    ),
  );
  return val;
});

Map<String, dynamic> _$RiskConfigurationResponseToJson(
  RiskConfigurationResponse instance,
) => <String, dynamic>{
  'error': instance.error,
  'message': instance.message,
  'code': instance.code,
  'data': instance.data.toJson(),
};
